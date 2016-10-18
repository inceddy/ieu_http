<?php

use ieu\Http\Request;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RequestTest extends \PHPUnit_Framework_TestCase {
	public function testRequestNative()
	{
		$request = Request::native();
	}

	public function testRequestDefault()
	{
		$request = new Request();

		$this->assertNull($request->get('unsetkey'));
		$this->assertEquals('get-test', $request->get('unsetkey', 'get-test'));

		$this->assertNull($request->post('unsetkey'));
		$this->assertEquals('post-test', $request->post('unsetkey', 'post-test'));

		$this->assertNull($request->files('unsetkey'));
		$this->assertEquals('files-test', $request->files('unsetkey', 'files-test'));

		$this->assertNull($request->server('unsetkey'));
		$this->assertEquals('server-test', $request->server('unsetkey', 'server-test'));

		$this->assertNull($request->cookie('unsetkey'));
		$this->assertEquals('cookie-test', $request->cookie('unsetkey', 'cookie-test'));

		$this->assertNull($request->session('unsetkey'));
		$this->assertEquals('session-test', $request->session('unsetkey', 'session-test'));

	}

	public function testRequestRequest()
	{
		$request = new Request(['post' => ['test' => true]]);
		$this->assertTrue($request->request('test'));
	}

	public function testSession()
	{
	}
}