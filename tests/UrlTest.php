<?php

use ieu\Http\Url;
use ieu\Http\Route;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class UrlTest extends \PHPUnit_Framework_TestCase {

	public function testUrlFromString()
	{
		$urlString = 'https://example.com/some/path/somefile.php?id=some_id&cache=false#anchor';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com/some/path/somefile.php?id=some_id&cache=false';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com/some/path/somefile.php';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);
	}

	public function testQuery()
	{
		$urlString = 'http://test.de';
		$urlObject = Url::from($urlString);

		$urlObject->setQuery(['a' => 1, 'b' => 2]);
		$this->assertEquals('http://test.de/?a=1&b=2', (string) $urlObject);

		$urlObject->appendQuery(['c' => 3]);
		$this->assertEquals('http://test.de/?a=1&b=2&c=3', (string) $urlObject);

		// Reset by array
		$clone = clone $urlObject;
		$clone->setQuery([]);
		$this->assertEquals('http://test.de', (string) $clone);

		// Reset by string
		$clone = clone $urlObject;
		$clone->setQuery('');
		$this->assertEquals('http://test.de', (string) $clone);

		// Append to empty
		$clone->appendQuery(['c' => 3]);
		$this->assertEquals('http://test.de/?c=3', (string) $clone);

		// Test dubble
		$urlObject = Url::from('http://test.de/?a=1');
		$urlObject->appendQuery(['a' => 2]);
		$this->assertEquals('http://test.de/?a=2', (string)$urlObject);

	}
}