<?php
/**
 * Part of the Joomla Framework Database Package
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Database\Mysqli;

use Joomla\Database\DatabaseQuery;
use Joomla\Database\Query\LimitableInterface;

/**
 * MySQLi Query Building Class.
 *
 * @since  1.0
 */
class MysqliQuery extends DatabaseQuery implements LimitableInterface
{
	/**
	 * The offset for the result set.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $offset;

	/**
	 * The limit for the result set.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $limit;

	/**
	 * Method to modify a query already in string format with the needed
	 * additions to make the query limited to a particular number of
	 * results, or start at a particular offset.
	 *
	 * @param   string   $query   The query in string format
	 * @param   integer  $limit   The limit for the result set
	 * @param   integer  $offset  The offset for the result set
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function processLimit($query, $limit, $offset = 0)
	{
		if ($limit > 0 || $offset > 0)
		{
			$query .= ' LIMIT ' . $offset . ', ' . $limit;
		}

		return $query;
	}

	/**
	 * Concatenates an array of column names or values.
	 *
	 * @param   array   $values     An array of values to concatenate.
	 * @param   string  $separator  As separator to place between each value.
	 *
	 * @return  string  The concatenated values.
	 *
	 * @since   1.0
	 */
	public function concatenate($values, $separator = null)
	{
		if ($separator)
		{
			$concat_string = 'CONCAT_WS(' . $this->quote($separator);

			foreach ($values as $value)
			{
				$concat_string .= ', ' . $value;
			}

			return $concat_string . ')';
		}
		else
		{
			return 'CONCAT(' . implode(',', $values) . ')';
		}
	}

	/**
	 * Sets the offset and limit for the result set, if the database driver supports it.
	 *
	 * Usage:
	 * $query->setLimit(100, 0); (retrieve 100 rows, starting at first record)
	 * $query->setLimit(50, 50); (retrieve 50 rows, starting at 50th record)
	 *
	 * @param   integer  $limit   The limit for the result set
	 * @param   integer  $offset  The offset for the result set
	 *
	 * @return  MysqliQuery  Returns this object to allow chaining.
	 *
	 * @since   1.0
	 */
	public function setLimit($limit = 0, $offset = 0)
	{
		$this->limit  = (int) $limit;
		$this->offset = (int) $offset;

		return $this;
	}
}
