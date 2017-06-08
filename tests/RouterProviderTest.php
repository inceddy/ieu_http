<?php

use ieu\Http\RouterProvider;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterProviderTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->container = new ieu\Container\Container;
		$this->container->factory('Request', [function(){
			$request = new RequestMock();
			$request->setUrl('http://steingrebe.de/prefix/test');

			return $request;
		}]);
		$this->container->value('TestValue', 'test-value');
		$this->container->provider('Router', new RouterProvider);
	}

	public function testRouting()
	{
		$this->container->config(['RouterProvider', function($router) {
			$test = $this;
			$router->get('prefix/{id}', ['Request', 'RouteParameter', 'TestValue', function($request, $parameter, $testValue) use ($test) {
				$test->assertInstanceOf(RequestMock::CLASS, $request);
				$test->assertEquals(['id' => 'test'], $parameter);
				$this->assertEquals('test-value', $testValue);

				return 'Not Empty Result';
			}]);
		}]);

		$this->assertInstanceOf(RouterProvider::CLASS, $this->container['Router']);

		$this->container['Router']->handle();
	}
}