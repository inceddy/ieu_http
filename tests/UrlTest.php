<?php

use ieu\Http\Url;
use ieu\Http\Route;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class UrlTest extends \PHPUnit_Framework_TestCase {

	public function testUrlFromString()
	{
		$urlString = 'https://example.com/some/path/?id=some_id&cache=false#anchor';
		$urlObject = Url::fromUrl($urlString);

		$this->assertEqual($urlString, (string)$urlObject);
	}
}