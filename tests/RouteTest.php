<?php

use ieu\Http\Url;
use ieu\Http\Route;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouteTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->url = $this->getMockBuilder(ieu\Http\Url::CLASS)
        	->getMock();

        $this->url->method('getPath')
            ->willReturn('test/path/user123');
	}

	public function testRouteIgnoresSlashes()
	{
		$route = new Route('/test/path/user123/');
		$this->assertTrue(null !== $route->parse($this->url));
	}

	public function testRouteWildcard()
	{
		$route = new Route('test/path/*');
		$this->assertTrue(null !== $route->parse($this->url));	
	}

	public function testRouteVariables()
	{
		$route = new Route('test/{path}/{user}');
		$this->assertEquals(['path' => 'path', 'user' => 'user123'], $route->parse($this->url));
	}

	public function testRouteVariableValidation()
	{
		$routeValid = (new Route('test/path/{user}'))
			->validate('user', 'user\d+');
		$this->assertEquals(['user' => 'user123'], $routeValid->parse($this->url));

		// Invalid pattern
		$routeInvalid = (new Route('test/path/{user}'))
			->validate('user', 'customer\d+');
		$this->assertEquals(null, $routeInvalid->parse($this->url));
	}
}