<?php

/**
 * Part of the Joomla Framework Test Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test;

use PHPUnit\Framework\TestCase;

/**
 * Static helper methods to assist unit testing PHP code.
 *
 * @since  1.0
 */
class TestHelper
{
    /**
     * Helper method that gets a protected or private property in a class by reflection.
     *
     * The property is searched in the class and all its parent classes. A class name is only accepted for static properties.
     *
     * @param   string|object  $objectOrClass  The object or class name from which to return the property value.
     * @param   string         $propertyName   The name of the property to return.
     *
     * @return  mixed  The value of the property.
     *
     * @since   1.0
     * @throws  \InvalidArgumentException if property not available, or not static when a class name is given.
     * @throws  \ReflectionException if the class does not exist.
     */
    public static function getValue($objectOrClass, $propertyName)
    {
        $property = self::findProperty($objectOrClass, $propertyName);

        if ($property->isStatic()) {
            return $property->getValue();
        }

        if (!\is_object($objectOrClass)) {
            throw new \InvalidArgumentException(
                sprintf('Property [%s] of class [%s] is not static, pass an object to read it', $propertyName, $objectOrClass)
            );
        }

        return $property->getValue($objectOrClass);
    }

    /**
     * Helper method that invokes a protected or private method in a class by reflection.
     *
     * Example usage:
     *
     * $this->assertTrue(TestHelper::invoke($this->object, 'methodName', 123));
     * where 123 is the input parameter for your method
     *
     * @param   object  $object         The object on which to invoke the method.
     * @param   string  $methodName     The name of the method to invoke.
     * @param   array   ...$methodArgs  The arguments to pass forward to the method being called
     *
     * @return  mixed
     *
     * @since   1.0
     * @throws  \ReflectionException
     */
    public static function invoke($object, $methodName, ...$methodArgs)
    {
        $method = new \ReflectionMethod($object, $methodName);

        return $method->invokeArgs(\is_object($object) ? $object : null, $methodArgs);
    }

    /**
     * Helper method that sets a protected or private property in a class by reflection.
     *
     * The property is searched in the class and all its parent classes.
     *
     * @param   object  $object        The object for which to set the property.
     * @param   string  $propertyName  The name of the property to set.
     * @param   mixed   $value         The value to set for the property.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  \InvalidArgumentException if property not available.
     * @throws  \ReflectionException if the class does not exist.
     */
    public static function setValue($object, $propertyName, $value)
    {
        self::findProperty($object, $propertyName)->setValue($object, $value);
    }

    /**
     * Finds a property in a class or its parent classes.
     *
     * Private properties of a parent class are not visible through the reflection of a child class, so the class hierarchy is walked.
     *
     * @param   string|object  $objectOrClass  The object or class name in which to look for the property.
     * @param   string         $propertyName   The name of the property.
     *
     * @return  \ReflectionProperty
     *
     * @since   __DEPLOY_VERSION__
     * @throws  \InvalidArgumentException if property not available.
     * @throws  \ReflectionException if the class does not exist.
     */
    private static function findProperty($objectOrClass, string $propertyName): \ReflectionProperty
    {
        $refl = new \ReflectionClass($objectOrClass);

        do {
            if ($refl->hasProperty($propertyName)) {
                return $refl->getProperty($propertyName);
            }
        } while ($refl = $refl->getParentClass());

        $class = \is_string($objectOrClass) ? $objectOrClass : \get_class($objectOrClass);

        throw new \InvalidArgumentException(sprintf('Invalid property [%s] for class [%s]', $propertyName, $class));
    }
}
