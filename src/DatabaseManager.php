<?php
/**
 * Part of the Joomla Framework Test Package
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test;

use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Test\Exception\DatabaseConnectionNotInitialised;
use Joomla\Test\Exception\MissingDatabaseCredentials;

/**
 * Helper class for building a database connection in the test environment
 *
 * @since  __DEPLOY_VERSION__
 */
class DatabaseManager
{
	/**
	 * The database connection for the test environment
	 *
	 * @var    DatabaseInterface|null
	 * @since  __DEPLOY_VERSION__
	 */
	protected $connection;

	/**
	 * The database factory
	 *
	 * @var    DatabaseFactory
	 * @since  __DEPLOY_VERSION__
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
	 * @since  __DEPLOY_VERSION__
	 */
	protected $params = [];

	/**
	 * DatabaseManager constructor.
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function __construct()
	{
		$this->dbFactory = new DatabaseFactory;
	}

	/**
	 * Clears the database tables of all data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  DatabaseConnectionNotInitialised
	 */
	public function clearTables(): void
	{
		if ($this->connection === null)
		{
			throw new DatabaseConnectionNotInitialised(
				sprintf(
					'The database connection has not been initialised, ensure you call %s::getConnection() first.',
					self::class
				)
			);
		}

		foreach ($this->connection->getTableList() as $table)
		{
			$this->connection->truncateTable($table);
		}
	}

	/**
	 * Creates the database for the test environment
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * @throws  DatabaseConnectionNotInitialised
	 * @throws  ExecutionFailureException
	 */
	public function createDatabase(): void
	{
		if ($this->connection === null)
		{
			throw new DatabaseConnectionNotInitialised(
				sprintf(
					'The database connection has not been initialised, ensure you call %s::getConnection() first.',
					self::class
				)
			);
		}

		try
		{
			$this->connection->createDatabase(
				(object) [
					'db_name' => $this->getDbName(),
					'db_user' => $this->params['user'],
				]
			);
		}
		catch (ExecutionFailureException $exception)
		{
			$stringToCheck = sprintf("Can't create database '%s'; database exists", $this->getDbName());

			// If database exists, we're good
			if (strpos($exception->getMessage(), $stringToCheck) !== false)
			{
				return;
			}

			throw $exception;
		}
	}

	/**
	 * Destroys the database for the test environment
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * @throws  DatabaseConnectionNotInitialised
	 * @throws  ExecutionFailureException
	 */
	public function dropDatabase(): void
	{
		if ($this->connection === null)
		{
			throw new DatabaseConnectionNotInitialised(
				sprintf(
					'The database connection has not been initialised, ensure you call %s::getConnection() first.',
					static::class
				)
			);
		}

		try
		{
			$this->connection->setQuery('DROP DATABASE ' . $this->connection->quoteName($this->getDbName()))->execute();
		}
		catch (ExecutionFailureException $exception)
		{
			$stringToCheck = sprintf("Can't drop database '%s'; database doesn't exist", $this->getDbName());

			// If database does not exist, we're good
			if (strpos($exception->getMessage(), $stringToCheck) !== false)
			{
				return;
			}

			throw $exception;
		}
	}

	/**
	 * Fetches the database driver, creating it if not yet set up
	 *
	 * @return  DatabaseInterface
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getConnection(): DatabaseInterface
	{
		if ($this->connection === null)
		{
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
	 * @since   __DEPLOY_VERSION__
	 */
	public function getDbName(): string
	{
		if (!isset($this->params['database']))
		{
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
	 * Create the DatabaseDriver object
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function createConnection(): void
	{
		$params = $this->params;

		// Prevent auto connection to the database as this may be called before the test database is created
		$params['select'] = false;

		$driver = $params['driver'];

		unset($params['driver']);

		$this->connection = $this->dbFactory->getDriver($driver, $params);
	}

	/**
	 * Initialize the parameter storage for the database connection
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  MissingDatabaseCredentials
	 */
	protected function initialiseParams(): void
	{
		if (empty($this->params))
		{
			$driver   = getenv('JOOMLA_TEST_DB_DRIVER') ?: 'mysqli';
			$host     = getenv('JOOMLA_TEST_DB_HOST');
			$user     = getenv('JOOMLA_TEST_DB_USER');
			$password = getenv('JOOMLA_TEST_DB_PASSWORD');
			$database = getenv('JOOMLA_TEST_DB_DATABASE');
			$prefix   = getenv('JOOMLA_TEST_DB_PREFIX') ?: '';

			if (empty($host) || empty($user) || empty($database))
			{
				throw new MissingDatabaseCredentials;
			}

			$this->params = [
				'driver'   => $driver,
				'host'     => $host,
				'user'     => $user,
				'password' => $password,
				'prefix'   => $prefix,
				'database' => $database,
			];
		}
	}
}
