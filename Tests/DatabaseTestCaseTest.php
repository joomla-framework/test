<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests;

use Joomla\Database\DatabaseInterface;
use Joomla\Test\DatabaseTestCase;

/**
 * Test class for Joomla\Test\DatabaseTestCase, run against the database configured in phpunit.xml.dist
 */
class DatabaseTestCaseTest extends DatabaseTestCase
{
    public function testTheConnectionIsSetUpBeforeTheTests(): void
    {
        $this->assertInstanceOf(DatabaseInterface::class, static::$connection);
        $this->assertSame(static::$connection, static::getDatabaseManager()->getConnection());
    }
}
