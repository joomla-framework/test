## Updating from v4 to v5

The following changes were made to the Test package between v4 and v5.

### Native types for the `TestHelper` methods

The methods of `Joomla\Test\TestHelper` have native parameter and return types:

```php
public static function getValue(object|string $objectOrClass, string $propertyName): mixed
public static function invoke(object|string $object, string $methodName, mixed ...$methodArgs): mixed
public static function setValue(object $object, string $propertyName, mixed $value): void
```

`TestHelper::setValue()` only accepts an object; passing a class name to set a static property, which PHP 8.3
deprecated in `ReflectionProperty::setValue()`, now raises a `TypeError`. Set static properties with
`(new \ReflectionProperty(Foo::class, 'bar'))->setValue(null, $value)` instead. Property and method names must be
strings.
