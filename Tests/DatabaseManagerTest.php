<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Test\DatabaseManager;
use Joomla\Test\Exception\DatabaseConnectionNotInitialised;
use Joomla\Test\Exception\MissingDatabaseCredentials;
use Joomla\Test\Tests\Stubs\PresetDatabaseManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Test\DatabaseManager
 */
class DatabaseManagerTest extends TestCase
{
    /**
     * Environment variables read by the manager
     */
    private const ENV_VARS = [
        'JOOMLA_TEST_DB_DRIVER',
        'JOOMLA_TEST_DB_HOST',
        'JOOMLA_TEST_DB_PORT',
        'JOOMLA_TEST_DB_USER',
        'JOOMLA_TEST_DB_PASSWORD',
        'JOOMLA_TEST_DB_DATABASE',
        'JOOMLA_TEST_DB_PREFIX',
        'JOOMLA_TEST_DB_SELECT',
        'JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE',
    ];

    /**
     * Environment before the test
     *
     * @var  array<string, string|false>
     */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Every test starts from the defaults: an in-memory SQLite database
        foreach (self::ENV_VARS as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }

        parent::tearDown();
    }

    public function testGetConnectionDefaultsToAnInMemorySqliteDatabase(): void
    {
        $manager    = new DatabaseManager();
        $connection = $manager->getConnection();

        $this->assertInstanceOf(DatabaseInterface::class, $connection);
        $this->assertSame('sqlite', $connection->getServerType());
        $this->assertSame(':memory:', $manager->getDbName());
        $this->assertSame($connection, $manager->getConnection(), 'The connection is created only once');
    }

    public function testGetDbNameThrowsBeforeTheParametersAreInitialised(): void
    {
        $this->expectException(\RuntimeException::class);

        (new DatabaseManager())->getDbName();
    }

    public function testClearTablesThrowsWithoutConnection(): void
    {
        $this->expectException(DatabaseConnectionNotInitialised::class);

        (new DatabaseManager())->clearTables();
    }

    public function testCreateDatabaseThrowsWithoutConnection(): void
    {
        $this->expectException(DatabaseConnectionNotInitialised::class);

        (new DatabaseManager())->createDatabase();
    }

    public function testDropDatabaseThrowsWithoutConnection(): void
    {
        $this->expectException(DatabaseConnectionNotInitialised::class);

        (new DatabaseManager())->dropDatabase();
    }

    public function testServerDriversRequireHostAndUser(): void
    {
        putenv('JOOMLA_TEST_DB_DRIVER=mysqli');
        putenv('JOOMLA_TEST_DB_DATABASE=joomla_test');

        $this->expectException(MissingDatabaseCredentials::class);

        (new DatabaseManager())->getConnection();
    }

    public function testClearTablesEmptiesEveryTable(): void
    {
        $manager    = new DatabaseManager();
        $connection = $manager->getConnection();

        $connection->setQuery('CREATE TABLE ' . $connection->quoteName('foo') . ' (id INTEGER)')->execute();
        $connection->setQuery('INSERT INTO ' . $connection->quoteName('foo') . ' VALUES (1), (2)')->execute();

        $manager->clearTables();

        $this->assertSame(0, (int) $connection->setQuery('SELECT COUNT(*) FROM ' . $connection->quoteName('foo'))->loadResult());
    }

    public function testDropDatabaseWarnsBeforeDroppingADatabaseThatDoesNotLookLikeATestDatabase(): void
    {
        $queries = [];
        $manager = new PresetDatabaseManager(['database' => 'production'], $this->createServerConnection($queries));

        $deprecations = $this->collectDeprecations(fn () => $manager->dropDatabase());

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('dropDatabase() is destroying data in the database "production"', $deprecations[0]);
        $this->assertStringContainsString('JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE=yes', $deprecations[0]);
        $this->assertCount(1, $queries, 'The database is still dropped in 4.x');
    }

    public function testDropDatabaseDoesNotWarnForATestDatabase(): void
    {
        $queries = [];
        $manager = new PresetDatabaseManager(['database' => 'Joomla_TEST_db'], $this->createServerConnection($queries));

        $this->assertSame([], $this->collectDeprecations(fn () => $manager->dropDatabase()));
        $this->assertCount(1, $queries);
    }

    public function testDestructiveOperationsCanBeConfirmedThroughTheEnvironment(): void
    {
        putenv('JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE=yes');

        $queries = [];
        $manager = new PresetDatabaseManager(['database' => 'production'], $this->createServerConnection($queries));

        $this->assertSame([], $this->collectDeprecations(fn () => $manager->dropDatabase()));
        $this->assertSame([], $this->collectDeprecations(fn () => $manager->clearTables()));
    }

    public function testClearTablesWarnsBeforeEmptyingADatabaseThatDoesNotLookLikeATestDatabase(): void
    {
        $queries = [];
        $manager = new PresetDatabaseManager(['database' => 'production'], $this->createServerConnection($queries));

        $deprecations = $this->collectDeprecations(fn () => $manager->clearTables());

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('clearTables() is destroying data in the database "production"', $deprecations[0]);
    }

    public function testClearTablesDoesNotWarnForAnInMemorySqliteDatabase(): void
    {
        $manager = new DatabaseManager();
        $manager->getConnection();

        $this->assertSame([], $this->collectDeprecations(fn () => $manager->clearTables()));
    }

    public function testDumpingTheManagerMasksThePassword(): void
    {
        $queries = [];
        $manager = new PresetDatabaseManager(['password' => 's3cr3t-pa55'], $this->createServerConnection($queries));

        $dump = print_r($manager, true);

        $this->assertStringNotContainsString('s3cr3t-pa55', $dump);
        $this->assertStringContainsString('********', $dump);
        $this->assertStringContainsString('joomla_test', $dump);
    }

    public static function dropDatabaseQueries(): \Generator
    {
        yield 'MySQL' => ['mysql', ['DROP DATABASE IF EXISTS `joomla_test`']];
        yield 'PostgreSQL' => ['postgresql', ['DROP DATABASE IF EXISTS "joomla_test"']];
        yield 'SQL Server' => ['mssql', ['USE [master]', "IF DB_ID(N'joomla_test') IS NOT NULL DROP DATABASE [joomla_test]"]];
    }

    #[DataProvider('dropDatabaseQueries')]
    public function testDropDatabaseDoesNotFailWhenTheDatabaseDoesNotExist(string $serverType, array $expectedQueries): void
    {
        $queries = [];
        $manager = new PresetDatabaseManager([], $this->createServerConnection($queries, $serverType));

        $manager->dropDatabase();

        $this->assertSame($expectedQueries, $queries);
    }

    public static function databaseExistsErrors(): \Generator
    {
        // Messages as reported with a German server locale
        yield 'MySQLi' => ['mysql', "Kann Datenbank 'joomla_test' nicht erzeugen. Datenbank existiert bereits", 1007];
        yield 'PDO MySQL' => ['mysql', "HY000, 1007, Kann Datenbank 'joomla_test' nicht erzeugen. Datenbank existiert bereits", 0];
        yield 'PDO PostgreSQL' => ['postgresql', '42P04, 7, FEHLER:  Datenbank »joomla_test« existiert bereits', 42];
        yield 'SQL Server' => ['mssql', "Die Datenbank 'joomla_test' ist bereits vorhanden.", 1801];

        // English messages
        yield 'MySQLi, English' => ['mysql', "Can't create database 'joomla_test'; database exists", 1007];
        yield 'PDO PostgreSQL, English' => ['postgresql', '42P04, 7, ERROR:  database "joomla_test" already exists', 42];
    }

    #[DataProvider('databaseExistsErrors')]
    public function testCreateDatabaseIgnoresThatTheDatabaseExists(string $serverType, string $message, int $code): void
    {
        $queries    = [];
        $connection = $this->createServerConnection($queries, $serverType);
        $connection->expects($this->once())
            ->method('createDatabase')
            ->willThrowException(new ExecutionFailureException('CREATE DATABASE joomla_test', $message, $code));

        (new PresetDatabaseManager([], $connection))->createDatabase();
    }

    public function testCreateDatabaseRethrowsOtherErrors(): void
    {
        $queries    = [];
        $connection = $this->createServerConnection($queries);
        $connection->method('createDatabase')
            ->willThrowException(new ExecutionFailureException('CREATE DATABASE joomla_test', "Access denied for user 'joomla'@'localhost' to database 'joomla_test'", 1044));

        $this->expectException(ExecutionFailureException::class);
        $this->expectExceptionMessage('Access denied');

        (new PresetDatabaseManager([], $connection))->createDatabase();
    }

    /**
     * Creates a connection mock for a database server that records the executed queries
     *
     * @param   string[]  $queries  Receives the executed queries
     */
    private function createServerConnection(array &$queries, string $serverType = 'mysql'): DatabaseInterface&MockObject
    {
        $quotes = ['mysql' => ['`', '`'], 'postgresql' => ['"', '"'], 'mssql' => ['[', ']']][$serverType];

        $connection = $this->createMock(DatabaseInterface::class);
        $connection->method('getServerType')->willReturn($serverType);
        $connection->method('getTableList')->willReturn([]);
        $connection->method('quote')->willReturnCallback(fn ($text) => ($serverType === 'mssql' ? 'N' : '') . "'" . $text . "'");
        $connection->method('quoteName')->willReturnCallback(fn ($name) => $quotes[0] . $name . $quotes[1]);
        $connection->method('setQuery')->willReturnCallback(
            function ($query) use (&$queries, $connection) {
                $queries[] = $query;

                return $connection;
            }
        );

        return $connection;
    }

    /**
     * Runs the callback and returns the messages of the deprecations it triggers
     *
     * @return  string[]
     */
    private function collectDeprecations(callable $callback): array
    {
        $messages = [];

        set_error_handler(
            function (int $errno, string $errstr) use (&$messages): bool {
                $messages[] = $errstr;

                return true;
            },
            E_USER_DEPRECATED
        );

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }
}
