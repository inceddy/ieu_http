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

		$this->assertEquals('anchor', $urlObject->fragment());

		$newUrlObject = $urlObject->withFragment('anchor2');

		$this->assertNotEquals($urlObject, $newUrlObject);
		$this->assertEquals('anchor2', $newUrlObject->fragment());
	}

	public function testQuery()
	{
		$urlString = 'http://test.de';
		$urlObject = Url::from($urlString);

		$newUrlObject = $urlObject->withQuery(['a' => 1, 'b' => 2]);

		$this->assertNotEquals($urlObject, $newUrlObject);
		$this->assertEquals('http://test.de/?a=1&b=2', (string) $newUrlObject);

		$newerUrlObject = $newUrlObject->withMergedQuery(['c' => 3]);

		$this->assertNotEquals($newUrlObject, $newerUrlObject);
		$this->assertEquals('http://test.de/?a=1&b=2&c=3', (string) $newerUrlObject);

		// Reset by array
		$clone = $urlObject->withQuery([]);
		$this->assertEquals('http://test.de', (string) $clone);

		// Reset by string
		$clone = $urlObject->withQuery('');
		$this->assertEquals('http://test.de', (string) $clone);

		// Append to empty
		$clone = $clone->withMergedQuery(['c' => 3]);
		$this->assertEquals('http://test.de/?c=3', (string) $clone);

		// Test dubble
		$urlObject = Url::from('http://test.de/?a=1')->withMergedQuery(['a' => 2]);
		$this->assertEquals('http://test.de/?a=2', (string)$urlObject);
	}

	public function testPath()
	{
		$url = Url::from('https://example.com/some/path/long');
		$this->assertTrue($url->test('/^some\/path/'));

		$this->assertEquals('some', $url->first());

		$this->assertEquals('path', $url->nth(1));

		$this->assertEquals('long', $url->last());

		$this->assertEquals(3, $url->length());

		$this->assertEquals('some/path/long', $urlObject->path());

		$this->assertEquals(['some', 'path', 'long'], $urlObject->pathArray());


		$url = 'https://example.com/some/path/long';

		$this->assertEquals('https://example.com/some/path', (string)Url::from($url)->withPath('some/path'));
		$this->assertEquals('https://example.com/some/path', (string)Url::from($url)->withPath(['some', 'path']));

		$this->assertEquals('https://example.com/prefix/some/path/long', (string)Url::from($url)->withPathPrepend(['prefix']));
		$this->assertEquals('https://example.com/prefix/some/path/long', (string)Url::from($url)->withPathPrepend('prefix'));

		$this->assertEquals('https://example.com/some/path/long/suffix', (string)Url::from($url)->withPathAppend(['suffix']));
		$this->assertEquals('https://example.com/some/path/long/suffix', (string)Url::from($url)->withPathAppend('suffix'));
	}

	public function testScheme()
	{
		$urlString = 'https://example.com/some/path/somefile.php';
		$urlObject = Url::from($urlString);

		$this->assertEquals('https', $urlObject->scheme());
	}

	public function testCredentials()
	{
		$urlString = 'https://username:password@example.com/some/path';
		$urlObject = Url::from($urlString);

		$this->assertEquals('username', $urlObject->user());
		$this->assertEquals('password', $urlObject->password());

		$newUrlObject = $urlObject->withUserAndPassword('username2', 'password2');

		$this->assertNotEquals($urlObject, $newUrlObject);
		$this->assertEquals('username2', $newUrlObject->user());
		$this->assertEquals('password2', $newUrlObject->password());

		// Unsetting user auto unsets password
		$newUrlObject = $urlObject->withUser(null);
		$this->assertEquals(null, $newUrlObject->user());
		$this->assertEquals(null, $newUrlObject->password());
	}

	public function testPort()
	{
		$urlString = 'https://some-server.de:8000';
		$urlObject = Url::from($urlString);

		$this->assertEquals(8000, $urlObject->port());

		// Change port
		$newUrlObject = $urlObject->withPort('8080');

		$this->assertNotEquals($urlObject, $newUrlObject);
		$this->assertEquals(8080, $newUrlObject->port());

		// Remove known ports if matching with scheme
		$newUrlObject = $urlObject->withPort('443');
		$this->assertEquals('https://some-server.de', (string)$newUrlObject);

		$newUrlObject = $urlObject->withScheme('ftp');
		$this->assertEquals(21, $newUrlObject->port());
	}


	/**
	 * @expectedException TypeError
	 */
	
	public function testInvalidPort()
	{
		$urlString = 'https://some-server.de:8000';
		$urlObject = Url::from($urlString);

		$urlObject->withPort('nonesense');
	}
}