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

/**
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 */

class HttpException extends \Exception {

	public function __construct($code)
	{
		if (!array_key_exists($code, Response::$statusCodeMap)) {
			trigger_error(sprintf('Http error code \'%s\' is unknown. Error code was set to 500.', $code), E_USER_ERROR);
			$code = 500;
		}
		parent::__construct(Response::$statusCodeMap[$code], $code);
	}
}