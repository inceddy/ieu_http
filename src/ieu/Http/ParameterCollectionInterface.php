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

interface ParameterCollectionInterface
{
	public function has($key);

	public function get($key);

	public function set($key, $value);

	public function push($key, $value);

	public function delete($key);

	public function __toString();
}