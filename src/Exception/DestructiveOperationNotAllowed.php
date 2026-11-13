<?php

/**
 * Part of the Joomla Framework Test Package
 *
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Exception;

/**
 * Exception thrown when dropping or emptying a database that is not recognisably a test database
 *
 * @since  __DEPLOY_VERSION__
 */
class DestructiveOperationNotAllowed extends \RuntimeException
{
}
