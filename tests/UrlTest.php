<?php

use ieu\Http\Url;
use ieu\Http\Route;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class UrlTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor()
	{
		$urlObject = new Url([
			'scheme' => 'stg',
			'host' => 'some-hostname.de'
		]);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	
	public function testInvalidConstructor()
	{
		$urlObject = new Url([
			'scheme' => null,
			'host' => null
		]);
	}


	public function testUrlFromString()
	{
		$urlString = 'https://username:password@example.com:8080/some/path/somefile.php?id=some_id&cache=false#anchor';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com:8080/some/path/somefile.php?id=some_id&cache=false#anchor';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com/some/path/somefile.php?id=some_id&cache=false#anchor';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com/some/path/somefile.php?id=some_id&cache=false';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com/some/path/somefile.php';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);

		$urlString = 'https://example.com/some/path';
		$urlObject = Url::from($urlString);

		$this->assertEquals($urlString, (string)$urlObject);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	
	public function testFromInvalidString()
	{
		$urlString = 'this-is.nonesense';
		$urlObject = Url::from($urlString);

		echo $urlObject;
	}

	public function testFragment()
	{
		$urlString = 'https://example.com/some/path/somefile.php?id=some_id&cache=false#anchor';
		$urlObject = Url::from($urlString);

		$this->assertEquals('anchor', $urlObject->getFragment());

		$urlObject->setFragment('anchor2');
		$this->assertEquals('anchor2', $urlObject->getFragment());
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

		// Test object
		$clone = clone $urlObject;
		$clone->setQuery((object)['a' => 1]);
		$this->assertEquals('http://test.de/?a=1', (string) $clone);
	}

	public function testFile()
	{
		$urlString = 'https://example.com/some/path/somefile.php';
		$urlObject = Url::from($urlString);

		$this->assertEquals('somefile.php', $urlObject->getFile());

		$urlObject->setFile('somefile2.php');
		$this->assertEquals('somefile2.php', $urlObject->getFile());
	}

	public function testPath()
	{
		$urlObject = Url::from('https://example.com/some/path/long/somefile.php');
		$this->assertTrue($urlObject->test('/^some\/path/'));

		$this->assertEquals('some', $urlObject->first());

		$this->assertEquals('path', $urlObject->nth(1));

		$this->assertEquals('long', $urlObject->last());

		// Path with query
		$urlObject->setPath('some/path/newfile.php?a=b');

		$this->assertEquals('newfile.php', $urlObject->getFile());
		$this->assertEquals('a=b', $urlObject->getQuery());

		$this->assertEquals(2, $urlObject->length());
	}

	public function testScheme()
	{
		$urlString = 'https://example.com/some/path/somefile.php';
		$urlObject = Url::from($urlString);

		$this->assertEquals('https', $urlObject->getScheme());
	}

	public function testCredentials()
	{
		$urlString = 'https://username:password@example.com/some/path';
		$urlObject = Url::from($urlString);

		$this->assertEquals('username', $urlObject->getUser());
		$this->assertEquals('password', $urlObject->getPassword());

		$urlObject->setUser('username2');
		$urlObject->setPassword('password2');

		$this->assertEquals('username2', $urlObject->getUser());
		$this->assertEquals('password2', $urlObject->getPassword());

		// Unsetting user auto unsets password
		$urlObject->setUser();
		$this->assertEquals(null, $urlObject->getUser());
		$this->assertEquals(null, $urlObject->getPassword());
	}

	public function testPort()
	{
		$urlString = 'https://some-server.de:8000';
		$urlObject = Url::from($urlString);

		$this->assertEquals('8000', $urlObject->getPort());

		// Change port
		$urlObject->setPort('8080');
		$this->assertEquals('8080', $urlObject->getPort());

		// Remove known ports if matching with scheme
		$urlObject->setPort('443');
		$this->assertEquals('https://some-server.de', (string)$urlObject);

		$urlObject->setScheme('ftp');
		$this->assertEquals(21, $urlObject->getPort());
	}


	/**
	 * @expectedException TypeError
	 */
	
	public function testInvalidPort()
	{
		$urlString = 'https://some-server.de:8000';
		$urlObject = Url::from($urlString);

		$urlObject->setPort('nonesense');
	}
}