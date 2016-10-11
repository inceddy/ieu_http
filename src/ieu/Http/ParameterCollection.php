<?php

/*
 * This file is part of ieUtilities HTTP.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ieu\Http;
use Countable;
use IteratorAggregate;
use ArrayIterator;

class ParameterCollection implements Countable, IteratorAggregate
{
	/**
	 * The parameter cache as key-value-storage.
	 * @var array
	 */
	
	private $parameters;

	public function __construct(array &$parameter)
	{

		$this->parameters = &$parameter;
	}

	public function has($key)
	{
		return array_key_exists($key, $this->parameters);
	}

	public function get($key) 
	{
		return $this->has($key) ? $this->parameters[$key] : null;
	}

	public function set($key, $value)
	{
		$this->parameters[$key] = $value;
	}

	public function push($key, $value)
	{
		// Not yet set
		if (!$this->has($key)) {
			return $this->set($key, [$value]);
		}

		// Already set with array value
		if (is_array($this->parameters[$key])) {
			$this->parameters[$key][] = $value;
			return $this;
		}

		// Set with single value
		$this->parameters[$key] = [$this->parameters[$key], $value];
		return $this;
	}

	public function delete($key)
	{
		unset($this->parameters[$key]);
	}

	public function __toString()
	{
		$string = '';

		foreach($this->parameters as $key => $parameter) {
			$string .= sprintf("%s = %s\r\n", $key, $parameter);
		}

		return $string;
	}


	/**
	 * Gets the parameter count of this collection to satisfy the Countable interface.
	 *
	 * @return int
	 * 
	 */
	
	public function count()
	{
		return count($this->parameter);
	}


	/**
	 * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
	 *
	 * @return ArrayIterator
	 * 
	 */
	
	public function getIterator()
	{
		return new ArrayIterator($this->parameters);
	}
}