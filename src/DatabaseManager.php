<?php

/**
 * Part of the Joomla Framework Test Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test;

use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Test\Exception\DatabaseConnectionNotInitialised;
use Joomla\Test\Exception\DestructiveOperationNotAllowed;
use Joomla\Test\Exception\MissingDatabaseCredentials;

/**
 * Helper class for building a database connection in the test environment
 *
 * @since  2.0.0
 */
class DatabaseManager
{
    /**
     * The database connection for the test environment
     *
     * @var    DatabaseInterface|null
     * @since  2.0.0
     */
    protected $connection;

    /**
     * The database factory
     *
     * @var    DatabaseFactory
     * @since  2.0.0
     */
    protected $dbFactory;

    /**
     * The database connection parameters from the environment configuration
     *
     * By default, this is seeded by a set of environment vars that you can set in your operating system environment
     * or phpunit.xml configuration file. You may also customise the parameter configuration behavior for your environment
     * if need be by extending the `initialiseParams()` method.
     *
     * @var    array
     * @since  2.0.0
     */
    protected $params = [];

    /**
     * DatabaseManager constructor.
     *
     * @since    2.0.0
     */
    public function __construct()
    {
        $this->dbFactory = new DatabaseFactory();
    }

    /**
     * Clears the database tables of all data
     *
     * @return  void
     *
     * @since   2.0.0
     * @throws  DatabaseConnectionNotInitialised
     * @throws  DestructiveOperationNotAllowed
     */
    public function clearTables(): void
    {
        if ($this->connection === null) {
            throw new DatabaseConnectionNotInitialised(
                sprintf(
                    'The database connection has not been initialised, ensure you call %s::getConnection() first.',
                    self::class
                )
            );
        }

        $this->checkDestructiveOperation(__FUNCTION__);

        foreach ($this->connection->getTableList() as $table) {
            $this->connection->truncateTable($table);
        }
    }

    /**
     * Creates the database for the test environment
     *
     * @return  void
     *
     * @since   2.0.0
     *
     * @throws  DatabaseConnectionNotInitialised
     * @throws  ExecutionFailureException
     */
    public function createDatabase(): void
    {
        if ($this->connection === null) {
            throw new DatabaseConnectionNotInitialised(
                sprintf(
                    'The database connection has not been initialised, ensure you call %s::getConnection() first.',
                    self::class
                )
            );
        }

        try {
            $this->connection->createDatabase(
                (object) [
                    'db_name' => $this->getDbName(),
                    'db_user' => $this->params['user'],
                ]
            );
        } catch (ExecutionFailureException $exception) {
            $stringsToCheck = [
                sprintf("Can't create database '%s'; database exists", $this->getDbName()),
                sprintf('database "%s" already exists', $this->getDbName()),
            ];

            foreach ($stringsToCheck as $stringToCheck) {
                // If database exists, we're good
                if (strpos($exception->getMessage(), $stringToCheck) !== false) {
                    return;
                }
            }

            throw $exception;
        }
    }

    /**
     * Destroys the database for the test environment
     *
     * @return  void
     *
     * @since   2.0.0
     *
     * @throws  DatabaseConnectionNotInitialised
     * @throws  DestructiveOperationNotAllowed
     * @throws  ExecutionFailureException
     */
    public function dropDatabase(): void
    {
        if ($this->connection === null) {
            throw new DatabaseConnectionNotInitialised(
                sprintf(
                    'The database connection has not been initialised, ensure you call %s::getConnection() first.',
                    static::class
                )
            );
        }

        // Skip if the database was auto-selected
        if ($this->params['select']) {
            return;
        }

        // Skip for SQLite (TODO - Implement a dropDatabase method in the database driver)
        if ($this->connection->getServerType() === 'sqlite') {
            return;
        }

        $this->checkDestructiveOperation(__FUNCTION__);

        // For SQL Server, we need to switch to another context first
        if ($this->connection->getServerType() === 'mssql') {
            $this->connection->setQuery('USE [master]')->execute();
        }

        try {
            $this->connection->setQuery('DROP DATABASE ' . $this->connection->quoteName($this->getDbName()))->execute();
        } catch (ExecutionFailureException $exception) {
            $stringsToCheck = [
                sprintf("Can't drop database '%s'; database doesn't exist", $this->getDbName()),
                sprintf("Cannot drop the database '%s', because it does not exist", $this->getDbName()),
            ];

            foreach ($stringsToCheck as $stringToCheck) {
                // If database exists, we're good
                if (strpos($exception->getMessage(), $stringToCheck) !== false) {
                    return;
                }
            }

            throw $exception;
        }
    }

    /**
     * Fetches the database driver, creating it if not yet set up
     *
     * @return  DatabaseInterface
     *
     * @since   2.0.0
     */
    public function getConnection(): DatabaseInterface
    {
        if ($this->connection === null) {
            $this->initialiseParams();
            $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * Fetch the name of the database to use
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getDbName(): string
    {
        if (!isset($this->params['database'])) {
            throw new \RuntimeException(
                sprintf(
                    'The database name is not set in the parameters, ensure you call %s::initialiseParams() first.',
                    static::class
                )
            );
        }

        return $this->params['database'];
    }

    /**
     * Returns the properties to show when the object is dumped, with the database password masked
     *
     * The connection is reduced to its class name because the driver keeps the password in its options.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __debugInfo(): array
    {
        $params = $this->params;

        if (!empty($params['password'])) {
            $params['password'] = '********';
        }

        return [
            'connection' => $this->connection === null ? null : \get_class($this->connection),
            'dbFactory'  => $this->dbFactory,
            'params'     => $params,
        ];
    }

    /**
     * Checks whether the database may be dropped or emptied
     *
     * Destructive operations are allowed on an in-memory SQLite database, on a database whose name contains "test",
     * or when the JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE environment variable is set to "yes". Otherwise, the operation is
     * refused.
     *
     * @param   string  $operation  The name of the method about to destroy data.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     * @throws  DestructiveOperationNotAllowed
     */
    protected function checkDestructiveOperation(string $operation): void
    {
        if ($this->isTestDatabase() || getenv('JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE') === 'yes') {
            return;
        }

        throw new DestructiveOperationNotAllowed(
            sprintf(
                '%1$s::%2$s() refuses to destroy data in the database "%3$s", whose name does not look like a test database.'
                . ' Use a database name containing "test" or set the environment variable JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE=yes'
                . ' to confirm.',
                static::class,
                $operation,
                $this->getDbName()
            )
        );
    }

    /**
     * Checks whether the configured database is recognisably a test database
     *
     * This is the case for an in-memory SQLite database and for a database whose name contains "test" (case-insensitive).
     * Extend this method if your test databases follow a different naming scheme.
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function isTestDatabase(): bool
    {
        $database = $this->getDbName();

        if ($database === ':memory:' && ($this->params['driver'] ?? null) === 'sqlite') {
            return true;
        }

        return stripos($database, 'test') !== false;
    }

    /**
     * Create the DatabaseDriver object
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function createConnection(): void
    {
        $params = $this->params;

        $driver = $params['driver'];

        unset($params['driver']);

        // Only pass the database name if the select param is set to true
        if (!$params['select']) {
            unset($params['database']);
        }

        $this->connection = $this->dbFactory->getDriver($driver, $params);
    }

    /**
     * Initialize the parameter storage for the database connection
     *
     * @return  void
     *
     * @since   2.0.0
     * @throws  MissingDatabaseCredentials
     */
    protected function initialiseParams(): void
    {
        if (empty($this->params)) {
            $driver   = getenv('JOOMLA_TEST_DB_DRIVER') ?: 'sqlite';
            $host     = getenv('JOOMLA_TEST_DB_HOST');
            $port     = getenv('JOOMLA_TEST_DB_PORT') ?: null;
            $user     = getenv('JOOMLA_TEST_DB_USER');
            $password = getenv('JOOMLA_TEST_DB_PASSWORD');
            $database = getenv('JOOMLA_TEST_DB_DATABASE') ?: ':memory:';
            $prefix   = getenv('JOOMLA_TEST_DB_PREFIX') ?: '';
            $select   = getenv('JOOMLA_TEST_DB_SELECT') === 'yes';

            // If using the SQLite driver and an in memory database, only the database is necessary, otherwise we need a host and user
            if ($driver === 'sqlite') {
                if ($database === ':memory:') {
                    // Everything is good
                } elseif (empty($host) || empty($user)) {
                    throw new MissingDatabaseCredentials();
                }
            } else {
                if (empty($host) || empty($user)) {
                    throw new MissingDatabaseCredentials();
                }
            }

            $this->params = [
                'driver'   => $driver,
                'host'     => $host,
                'port'     => $port,
                'user'     => $user,
                'password' => $password,
                'prefix'   => $prefix,
                'database' => $database,
                'select'   => $select,
            ];
        }
    }
}
