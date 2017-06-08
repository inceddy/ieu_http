<?php

use ieu\Http\Request;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RequestTest extends \PHPUnit_Framework_TestCase {

	public function testRequestDefault()
	{
		$request = Request::native();

		$this->assertNull($request->get('unsetkey'));
		$this->assertEquals('get-test', $request->get('unsetkey', 'get-test'));

		$this->assertNull($request->post('unsetkey'));
		$this->assertEquals('post-test', $request->post('unsetkey', 'post-test'));

		$this->assertNull($request->files('unsetkey'));
		$this->assertEquals('files-test', $request->files('unsetkey', 'files-test'));

		$this->assertNull($request->header('unsetkey'));
		$this->assertEquals('header-test', $request->header('unsetkey', 'header-test'));

		$this->assertNull($request->server('unsetkey'));
		$this->assertEquals('server-test', $request->server('unsetkey', 'server-test'));

		$this->assertNull($request->cookie('unsetkey'));
		$this->assertEquals('cookie-test', $request->cookie('unsetkey', 'cookie-test'));

		$this->assertNull($request->session('unsetkey'));
		$this->assertEquals('session-test', $request->session('unsetkey', 'session-test'));
	}

	public function testRequestParameterAccess()
	{
		$request = new Request([
			'get'  => ['param' => null],
			'post' => ['param' => true]
		]);

		$this->assertTrue($request->request('param'));

		$request = new Request([
			'get'    => ['get'    => true],
			'post'   => ['post'   => true],
			'files'  => ['files'  => true],
			'header' => ['header' => true]
		]);

		$this->assertTrue($request->get('get'));
		$this->assertTrue($request->post('post'));
		$this->assertTrue($request->files('files'));
		$this->assertTrue($request->header('header'));
	}

	public function testSession()
	{
	}
}