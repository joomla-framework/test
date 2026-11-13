# Test package

## TestHelper

`Joomla\Test\TestHelper` is a static helper class that can be used to take some of the pain out of repetitive tasks whilst unit testing with PHPUnit.

### Mocking

There are two methods that help with PHPUnit mock objects.

#### `TestHelper::assignMockCallbacks`

This helper method provides an easy way to configure mock callbacks in bulk.

```php
use Joomla\Test\TestHelper;

class FooTest extends \PHPUnit_Framework_TestCase
{
	public function testFoo()
	{
		// Create the mock.
		$mockFoo = $this->getMock(
			'Foo',
			// Methods array.
			array(),
			// Constructor arguments.
			array(),
			// Mock class name.
			'',
			// Call original constructor.
			false
		);

		$mockCallbacks = array(
			// 'Method Name' => <callback>
			'method1' => array('\mockFoo', 'method1'),
			'method2' => array($this, 'mockMethod2'),
		);

		TestHelper::assignMockCallbacks($mockFoo, $this, $mockCallbacks);
	}

	public function mockMethod2($value)
	{
		return strtolower($value);
	}
}

```

#### `TestHelper::assignMockReturns`

This helper method provides an easy way to configure mock returns values in bulk.

```php
use Joomla\Test\TestHelper;

class FooTest extends \PHPUnit_Framework_TestCase
{
	public function testFoo()
	{
		// Create the mock.
		$mockFoo = $this->getMock(
			'Foo',
			// Methods array.
			array(),
			// Constructor arguments.
			array(),
			// Mock class name.
			'',
			// Call original constructor.
			false
		);

		$mockReturns = array(
			// 'Method Name' => 'Canned return value'
			'method1' => 'canned result 1',
			'method2' => 'canned result 2',
			'method3' => 'canned result 3',
		);

		TestHelper::assignMockReturns($mockFoo, $this, $mockReturns);
	}
}

```

### Reflection

There are three methods that help with reflection.

#### `TestHelper::getValue`

The `TestHelper::getValue` method allows you to get the value of any protected or private property.

```php
use Joomla\Test\TestHelper;

class FooTest extends \PHPUnit_Framework_TestCase
{
	public function testFoo()
	{
		$instance = new \Foo;

		// Get the value of a protected `bar` property.
		$value = TestHelper::getValue($instance, 'bar');
	}
}

```

This method should be used sparingly. It is usually more appropriate to use PHPunit's `assertAttribute*` methods.

#### `TestHelper::setValue`

The `TestHelper::setValue` method allows you to set the value of any protected or private property.

```php
use Joomla\Test\TestHelper;

class FooTest extends \PHPUnit_Framework_TestCase
{
	public function testFoo()
	{
		$instance = new \Foo;

		// Set the value of a protected `bar` property.
		TestHelper::setValue($instance, 'bar', 'New Value');
	}
}

```

This method is useful for injecting values into an object for the purpose of testing getter methods.

#### `TestHelper::invoke`

The `TestHelper::invoke` method allow you to invoke any protected or private method. After specifying the object and the method name, any remaining arguments are passed to the method being invoked.

```php
use Joomla\Test\TestHelper;

class FooTest extends \PHPUnit_Framework_TestCase
{
	public function testFoo()
	{
		$instance = new \Foo;

		// Invoke the protected `bar` method.
		$value1 = TestHelper::invoke($instance, 'bar');

		// Invoke the protected `bar` method with arguments.
		$value2 = TestHelper::invoke($instance, 'bar', 'arg1', 'arg2');
	}
}
```

## DatabaseTestCase

`Joomla\Test\DatabaseTestCase` is a base class for PHPUnit tests that need a database connection. It requires
`joomla/database`. Before the first test of a class, it drops and recreates the configured database and selects it;
after the last test, it drops the database again. The connection is available as `static::$connection`.

`DatabaseManager::dropDatabase()` does nothing if the database does not exist (`DROP DATABASE IF EXISTS`; on SQL Server
a `DB_ID()` check), and `DatabaseManager::createDatabase()` accepts a database that already exists. Both work
regardless of the language the database server reports its errors in.

```php
use Joomla\Test\DatabaseTestCase;

class ArticleModelTest extends DatabaseTestCase
{
	public function testLoad()
	{
		$model = new ArticleModel(static::$connection);

		// ...
	}
}
```

The connection is built by `Joomla\Test\DatabaseManager` from these environment variables, which you can set in
your `phpunit.xml` with `<env>` elements:

| Variable | Default | Description |
|---|---|---|
| `JOOMLA_TEST_DB_DRIVER` | `sqlite` | The database driver, e.g. `mysqli`, `mysql`, `pgsql`, `sqlsrv` |
| `JOOMLA_TEST_DB_HOST` | | The database host; required for every driver except SQLite |
| `JOOMLA_TEST_DB_PORT` | | The database port |
| `JOOMLA_TEST_DB_USER` | | The database user; required for every driver except SQLite |
| `JOOMLA_TEST_DB_PASSWORD` | | The database password |
| `JOOMLA_TEST_DB_DATABASE` | `:memory:` | The name of the test database; for SQLite `:memory:` or the path of the database file |
| `JOOMLA_TEST_DB_PREFIX` | | The table prefix |
| `JOOMLA_TEST_DB_SELECT` | | `yes` to connect to an existing database; it is then neither dropped nor recreated |
| `JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE` | | `yes` to confirm that the database may be dropped and emptied although its name does not contain "test" |

If the host or user is missing, the tests are skipped. To build the connection differently, extend
`DatabaseManager` and override `DatabaseTestCase::createDatabaseManager()`.

### Protection against dropping a real database

**The configured database is dropped and recreated for every test class.** `DatabaseManager::dropDatabase()` and
`DatabaseManager::clearTables()` therefore check that the database is recognisably a test database: an in-memory
SQLite database, or a database whose name contains "test" (case-insensitive). For any other database they trigger
an `E_USER_DEPRECATED` notice that names the database before going ahead; starting with 5.0 they will refuse to run.
Use a database name containing "test", set `JOOMLA_TEST_DB_ALLOW_DESTRUCTIVE=yes`, or override
`DatabaseManager::isTestDatabase()` if your test databases follow a different naming scheme.

When a `DatabaseManager` is dumped with `var_dump()` or `print_r()`, the database password is masked and the
connection is shown by its class name only.
