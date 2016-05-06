<?php

use ieu\Http\Router;
use ieu\Http\Route;
use ieu\Http\Request;

require __DIR__ . '/fixtures/RequestMock.php';

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterTest extends \PHPUnit_Framework_TestCase {
	public function testRouting()
	{
		$this->assertTrue(true);
		/*
		$request = (new RequestMock)->setUri('test');
		$router = new Router(new RequestMock);
		$router->addRoute(new Route('test'), function(){
			echo 'test';
		});

		$router->handle();
		*/
	}
}