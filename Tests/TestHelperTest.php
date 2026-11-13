<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests;

use Joomla\Test\TestHelper;
use Joomla\Test\Tests\Stubs\ChildClass;
use Joomla\Test\Tests\Stubs\GrandChildClass;
use Joomla\Test\Tests\Stubs\RootClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Test\TestHelper
 */
class TestHelperTest extends TestCase
{
    public function testGetValueReadsAPrivatePropertyOfTheClass(): void
    {
        $this->assertSame('child private', TestHelper::getValue(new ChildClass(), 'childPrivate'));
    }

    public function testGetValueReadsAProtectedPropertyOfTheParentClass(): void
    {
        $this->assertSame('parent protected', TestHelper::getValue(new ChildClass(), 'parentProtected'));
    }

    public function testGetValueReadsAPrivatePropertyOfTheParentClass(): void
    {
        $this->assertSame('parent private', TestHelper::getValue(new ChildClass(), 'parentPrivate'));
    }

    public function testGetValueThrowsForAnUnknownPropertyOfAClassWithoutParent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property [unknown] for class [' . RootClass::class . ']');

        TestHelper::getValue(new RootClass(), 'unknown');
    }

    public function testSetValueWritesAPrivatePropertyOfTheClass(): void
    {
        $object = new ChildClass();

        TestHelper::setValue($object, 'childPrivate', 'changed');

        $this->assertSame('changed', TestHelper::getValue($object, 'childPrivate'));
    }

    public function testSetValueWritesAPrivatePropertyOfTheParentClass(): void
    {
        $object = new ChildClass();

        TestHelper::setValue($object, 'parentPrivate', 'changed');

        $this->assertSame('changed', TestHelper::getValue($object, 'parentPrivate'));
    }

    public function testGetValueReadsAPrivatePropertyOfAGrandparentClass(): void
    {
        $this->assertSame('parent private', TestHelper::getValue(new GrandChildClass(), 'parentPrivate'));
    }

    public function testGetValueThrowsForAnUnknownPropertyOfAClassWithParents(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property [unknown] for class [' . GrandChildClass::class . ']');

        TestHelper::getValue(new GrandChildClass(), 'unknown');
    }

    public function testGetValueReadsAStaticPropertyByClassName(): void
    {
        $this->assertSame('static', TestHelper::getValue(ChildClass::class, 'staticProperty'));
    }

    public function testGetValueThrowsForANonStaticPropertyByClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property [childPrivate] of class [' . ChildClass::class . '] is not static');

        TestHelper::getValue(ChildClass::class, 'childPrivate');
    }

    public function testSetValueWritesAPrivatePropertyOfAGrandparentClass(): void
    {
        $object = new GrandChildClass();

        TestHelper::setValue($object, 'parentPrivate', 'changed');

        $this->assertSame('changed', TestHelper::getValue($object, 'parentPrivate'));
    }

    public function testSetValueThrowsForAnUnknownPropertyOfAClassWithoutParent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property [unknown] for class [' . RootClass::class . ']');

        TestHelper::setValue(new RootClass(), 'unknown', 'value');
    }

    public function testSetValueThrowsForAnUnknownPropertyOfAClassWithParents(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property [unknown] for class [' . GrandChildClass::class . ']');

        TestHelper::setValue(new GrandChildClass(), 'unknown', 'value');
    }

    public function testInvokeCallsAProtectedMethodWithArguments(): void
    {
        $this->assertSame(['a', 'b'], TestHelper::invoke(new ChildClass(), 'protectedMethod', 'a', 'b'));
    }

    public function testInvokeCallsAPrivateStaticMethodByClassName(): void
    {
        $this->assertSame('static value', TestHelper::invoke(ChildClass::class, 'privateStaticMethod', 'value'));
    }
}
