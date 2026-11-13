<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests\Stubs;

use Joomla\Database\DatabaseInterface;
use Joomla\Test\DatabaseManager;

/**
 * Database manager with preset parameters and connection, so no database server is needed
 */
class PresetDatabaseManager extends DatabaseManager
{
    public function __construct(array $params, DatabaseInterface $connection)
    {
        parent::__construct();

        $this->params     = $params + [
            'driver'   => 'mysqli',
            'host'     => 'localhost',
            'port'     => null,
            'user'     => 'root',
            'password' => '',
            'prefix'   => '',
            'database' => 'joomla_test',
            'select'   => false,
        ];
        $this->connection = $connection;
    }
}
