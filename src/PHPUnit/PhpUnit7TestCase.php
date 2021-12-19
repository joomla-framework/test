<?php
/**
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\PHPUnit;

use PHPUnit\Framework\TestCase;

/**
 * Compatibility test case used for PHPUnit 7.x and later
 *
 * @since 1.4.0
 */
abstract class PhpUnit7TestCase extends TestCase
{
	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		static::doSetUpBeforeClass();
	}

	/**
	 * This method is called after the last test of this test class is run.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void
	{
		static::doTearDownAfterClass();
	}

	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->doSetUp();
	}

	/**
	 * Tears down the fixture, for example, close a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$this->doTearDown();
	}

	/**
	 * Compatibility method for `setUpBeforeClass()`
	 *
	 * @return void
	 */
	protected static function doSetUpBeforeClass()
	{
		parent::setUpBeforeClass();
	}

	/**
	 * Compatibility method for `tearDownAfterClass()`
	 *
	 * @return void
	 */
	protected static function doTearDownAfterClass()
	{
		parent::tearDownAfterClass();
	}

	/**
	 * Compatibility method for `setUp()`
	 *
	 * @return void
	 */
	protected function doSetUp()
	{
		parent::setUp();
	}

	/**
	 * Compatibility method for `tearDown()`
	 *
	 * @return void
	 */
	protected function doTearDown()
	{
		parent::tearDown();
	}
}
