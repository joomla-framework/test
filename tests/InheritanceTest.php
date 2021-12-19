<?php
/**
 * @copyright  Copyright (C) 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Test\Tests;

use Joomla\Test\PHPUnit\TestCase;

class InheritanceTest extends TestCase
{
	static protected $setUpBeforeClassCalled = 0;
	static protected $setUpCalled = 0;
	static protected $tearDownCalled = 0;

	public static function doSetUpBeforeClass()
	{
		self::$setUpBeforeClassCalled++;
	}

	public function doSetUp()
	{
		self::$setUpCalled++;
	}

	public function doTearDown()
	{
		self::$tearDownCalled++;
	}

	/**
	 * @testdox setUp() and tearDown() are replaced by doSetUp() and doTearDown()
	 *
	 * ATTN: This test MUST be the first test in this class!
	 *
	 * @return int
	 */
	public function testSetUpCalledFirstTime()
	{
		self::assertEquals(
			0,
			self::$tearDownCalled,
			'doTearDown should not have been called yet'
		);

		self::assertEquals(
			1,
			self::$setUpCalled,
			'doSetUp() should have been called once more than doTearDown()'
		);

		return self::$setUpCalled;
	}

	/**
	 * @testdox doSetUp() and doTearDown() are called before rsp. after each test
	 *
	 * @depends testSetUpCalledFirstTime
	 *
	 * @param $callsBefore
	 *
	 * @return int
	 */
	public function testSetUpCalledSecondTime($callsBefore)
	{
		self::assertEquals(
			$callsBefore,
			self::$tearDownCalled,
			'doTearDown should have been called as often as doSetUp() in the prior test'
		);

		self::assertEquals(
			$callsBefore + 1,
			self::$setUpCalled,
			'doSetUp() should have been called once more than doTearDown()'
		);

		return self::$setUpCalled;
	}

	/**
	 * @testdox setUpBeforeClass() is replaced by doSetUpBeforeClass()
	 *
	 * @depends testSetUpCalledSecondTime
	 *
	 * @param $callsBefore
	 */
	public function testSetUpBeforeClassCalled($callsBefore)
	{
		self::assertEquals(
			1,
			self::$setUpBeforeClassCalled,
			'doSetUpBeforeClass() should have been called once'
		);
	}
}
