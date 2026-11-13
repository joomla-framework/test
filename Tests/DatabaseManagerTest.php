<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests;

use Joomla\Database\DatabaseInterface;
use Joomla\Test\DatabaseManager;
use Joomla\Test\Exception\DatabaseConnectionNotInitialised;
use Joomla\Test\Exception\DestructiveOperationNotAllowed;
use Joomla\Test\Exception\MissingDatabaseCredentials;
use Joomla\Test\Tests\Stubs\PresetDatabaseManager;
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

    public function testDropDatabaseRefusesToDropADatabaseThatDoesNotLookLikeATestDatabase(): void
    {
        $queries = [];
        $manager = new PresetDatabaseManager(['database' => 'production'], $this->createServerConnection($queries));

        try {
            $manager->dropDatabase();
            $this->fail('dropDatabase() should refuse to drop "production"');
        } catch (DestructiveOperationNotAllowed $exception) {
            $this->assertStringContainsString('dropDatabase() refuses to destroy data in the database "production"', $exception->getMessage());
            $this->assertStringContainsString('JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE=yes', $exception->getMessage());
        }

        $this->assertSame([], $queries, 'No query is executed');
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

    public function testClearTablesRefusesToEmptyADatabaseThatDoesNotLookLikeATestDatabase(): void
    {
        $queries    = [];
        $connection = $this->createServerConnection($queries);
        $connection->expects($this->never())->method('truncateTable');

        $this->expectException(DestructiveOperationNotAllowed::class);
        $this->expectExceptionMessage('clearTables() refuses to destroy data in the database "production"');

        (new PresetDatabaseManager(['database' => 'production'], $connection))->clearTables();
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

    /**
     * Creates a connection mock for a database server that records the executed queries
     *
     * @param   string[]  $queries  Receives the executed queries
     */
    private function createServerConnection(array &$queries): DatabaseInterface
    {
        $connection = $this->createMock(DatabaseInterface::class);
        $connection->method('getServerType')->willReturn('mysql');
        $connection->method('getTableList')->willReturn([]);
        $connection->method('quoteName')->willReturnCallback(fn ($name) => '`' . $name . '`');
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
