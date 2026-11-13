<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests;

use Joomla\Database\DatabaseInterface;
use Joomla\Test\DatabaseManager;
use Joomla\Test\Exception\DatabaseConnectionNotInitialised;
use Joomla\Test\Exception\MissingDatabaseCredentials;
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
}
