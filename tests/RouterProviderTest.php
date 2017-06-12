<?php

use ieu\Http\RouterProvider;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterProviderTest extends \PHPUnit_Framework_TestCase {

	public function getContainer()
	{
		return (new ieu\Container\Container)
		->factory('Request', [function(){
			$request = $this->getMockBuilder(ieu\Http\Request::CLASS)->getMock();
			$request->method('getUrl')->willReturn(
				ieu\Http\Url::from('http://steingrebe.de/prefix/test')
			);
			$request->method('isMethod')->willReturn(true);
			return $request;
		}])
		->value('TestValue', 'test-value')
		->provider('Router', new RouterProvider);
	}

	public function testInstanceSetup()
	{
		$container = $this->getContainer();
		$this->assertInstanceOf(RouterProvider::CLASS, $container['Router']);
	}

	public function testRouting()
	{
		$container = $this->getContainer();

		$container->config(['RouterProvider', function($router) {
			$test = $this;
			$router->get('prefix/{id}', ['Request', 'RouteParameter', 'TestValue', function($request, $parameter, $testValue) use ($test) {
				$this->assertTrue(is_object($request));
				$test->assertEquals(['id' => 'test'], $parameter);
				$this->assertEquals('test-value', $testValue);

				return 'Not Empty Result';
			}]);
		}]);


		$container['Router']->handle();
	}

	public function testDefaultHandler()
	{
		$container = $this->getContainer();

		$container->config(['RouterProvider', function($router) {
			$test = $this;
			$router->otherwise(['Request', 'Error', 'TestValue', function($request, $error, $testValue){
				$this->assertTrue(is_object($request));
				$test->assertNull($error);
				$this->assertEquals('test-value', $testValue);
			}]);
		}]);

		$container['Router']->handle();
	}
}