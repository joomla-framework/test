<?php
/**
 * Part of the Joomla Framework Test Package
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test;

use Joomla\Application\AbstractWebApplication;

/**
 * Inspector for the Joomla\Application\AbstractWebApplication class.
 *
 * @since       1.0
 * @deprecated  2.0  Deprecated without replacement
 */
class WebInspector extends AbstractWebApplication
{
	/**
	 * True to mimic the headers already being sent.
	 *
	 * @var     boolean
	 * @since   1.0
	 */
	public static $headersSent = false;

	/**
	 * True to mimic the connection being alive.
	 *
	 * @var     boolean
	 * @since   1.0
	 */
	public static $connectionAlive = true;

	/**
	 * List of sent headers for inspection. array($string, $replace, $code).
	 *
	 * @var     array
	 * @since   1.0
	 */
	public $headers = array();

	/**
	 * The exit code if the application was closed otherwise null.
	 *
	 * @var     integer
	 * @since   1.0
	 */
	public $closed;

	/**
	 * Allows public access to protected method.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function checkConnectionAlive()
	{
		return self::$connectionAlive;
	}

	/**
	 * Allows public access to protected method.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function checkHeadersSent()
	{
		return self::$headersSent;
	}

	/**
	 * Mimic exiting the application.
	 *
	 * @param   integer  $code  The exit code (optional; default is 0).
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function close($code = 0)
	{
		$this->closed = $code;
	}

	/**
	 * Allows public access to protected method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
	}

	/**
	 * Allows public access to protected method.
	 *
	 * @param   string   $string   The header string.
	 * @param   boolean  $replace  The optional replace parameter indicates whether the header should
	 *                             replace a previous similar header, or add a second header of the same type.
	 * @param   integer  $code     Forces the HTTP response code to the specified value. Note that
	 *                             this parameter only has an effect if the string is not empty.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function header($string, $replace = true, $code = null)
	{
		$this->headers[] = array($string, $replace, $code);
	}
}
