# The Test Package

[![Latest Stable Version](https://poser.pugx.org/joomla/test/v/stable)](https://packagist.org/packages/joomla/test)
[![Total Downloads](https://poser.pugx.org/joomla/test/downloads)](https://packagist.org/packages/joomla/test)
[![Latest Unstable Version](https://poser.pugx.org/joomla/test/v/unstable)](https://packagist.org/packages/joomla/test)
[![License](https://poser.pugx.org/joomla/test/license)](https://packagist.org/packages/joomla/test)

This package is a collection of tools that make some jobs of unit testing easier.

## TestCase

With version 7.0, PHPUnit added return type declarations to its TestCase class.
Tests running fine PHP < 7.2 will not work on current PHP versions,
because the signatures do not match.

This package provides a comatibility layer catering for that issue since version 1.4.2.
Just extend your test classes from
* `Joomla\Test\TestCase` instead of `PHPUnit\Framework\TestCase` and use method names
* `doSetUp()` instead of `setUp()`,
* `doTearDown()` instead of `tearDown()`,
* `doSetUpBeforeClass()` instead of `setUpBeforeClass()` and
* `doTearDownAfterClass()` instead of `tearDownAfterClass()`.

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

## Installation via Composer

Add `"joomla/test": "^1.4.2"` to the require-dev block in your composer.json and then run `composer install`.

```json
{
	"require-dev": {
		"joomla/test": "^1.4.2"
	}
}
```

Alternatively, you can simply run the following from the command line:

```sh
composer require -- dev joomla/test "^1.4.2"
```
