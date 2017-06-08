<?php

use ieu\Http\Router;
use ieu\Http\Route;
use ieu\Http\Request;

require __DIR__ . '/fixtures/RequestMock.php';
require __DIR__ . '/fixtures/Middleware.php';

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->request = (new RequestMock)->setUrl('http://steingrebe.de/prefix/test?some=value#hash');
	}

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage No matching route found. Set a default handler to catch this case.
   */
	public function testEmptyResultLeedsToException()
	{
		$test = $this;
		$router = new Router($this->request);
		$router->get('prefix/test', function() use ($test) {
		})->handle();
	}


	public function testRoutingGet()
	{
		$test = $this;
		$router = new Router($this->request);
		$router->get('prefix/test', function() use ($test) {
			$test->assertTrue(true);
			return 'Response!';
		})->handle();
	}

	public function testRoutingPost()
	{
		$test = $this;
		$router = new Router($this->request);
		$router->post('prefix/test', function() use ($test) {
			$test->assertTrue(true);
			return 'Response!';
		})->handle();
	}

	public function testRoutingRequest()
	{
		$test = $this;
		$router = new Router($this->request);
		$router->request('prefix/test', Request::HTTP_ALL, function() use ($test) {
			$test->assertTrue(true);
			return 'Response!';
		})->handle();
	}

	public function testContext()
	{
		$test = $this;
		$router = new Router($this->request);
		$router->context(function() use ($test){
			$this->route(new Route('prefix/test'), function() use ($test) {
				$test->assertTrue(true);
				return 'Response!';
			});
		})->handle();
	}

	public function testPrefix()
	{
		$test = $this;
		$router = new Router($this->request);
		$router->context(function() use ($test) {
			$this->prefix('prefix');
			$this->get('test', function() use ($test) {
				$test->assertTrue(true);
				return 'Response!';
			});
		})->handle();
	}

	public function testMiddleware()
	{
		$test = $this;
		$router = new Router($this->request);
		$router
			// Closure middleware
			->middleware(function(Closure $next, ...$args) {
				return $next('test', ...$args);
			})
			// Object middleware
			->middleware(new Middleware('test'))
			->get('prefix/test', function($arg1, $arg2, $request, $params) use ($test) {
				$this->assertEquals('test', $arg1);
				$this->assertEquals('test', $arg2);
				$this->assertInstanceOf(RequestMock::CLASS, $request);
				$this->assertEquals([], $params);
				return 'Middleware Response!';
			})
			->handle();
	}

	public function testDefaultHandlerOnEmptyResult()
	{
		$test = $this;
		(new Router($this->request))
			->otherwise(function() use ($test) {
				// Default handler is called
				$test->assertTrue(true);
			})
			->get('prefix/test', function() use ($test) {
				// Handler gets called but has no result
				$test->assertTrue(true);
			})
			->handle();
	}

	public function testDefaultHandlerOnMissingMatch()
	{
		$test = $this;
		(new Router($this->request))
			->otherwise(function() use ($test) {
				// Default handler is called
				$test->assertTrue(true);
			})
			->get('this/does/not/match', function() use ($test) {
				// Handler gets called never called!
				$test->assertTrue(false);
			})
			->handle();
	}

	public function testDefaultHandlerOnException()
	{
		$test = $this;
		(new Router($this->request))
			->otherwise(function($request, $exception) use ($test) {
				$test->assertInstanceOf(Exception::CLASS, $exception);
				$test->assertEquals('Route error', $exception->getMessage());
			})
			->get('prefix/test', function() use ($test) {
				throw new Exception('Route error');
			})
			->handle();
	}
}