<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests\Stubs;

class ChildClass extends ParentClass
{
    private $childPrivate = 'child private';

    protected function protectedMethod(...$args)
    {
        return $args;
    }

    private static function privateStaticMethod($value)
    {
        return 'static ' . $value;
    }
}
