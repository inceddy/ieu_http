<?php

use ieu\Http\Url;
use ieu\Http\Route;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouteTest extends \PHPUnit_Framework_TestCase {
	public function testRoute()
	{
        $url = $this->getMockBuilder(ieu\Http\Url::CLASS)
                    ->getMock();

        $url->method('uri')
            ->willReturn('test/path');

		$route = new Route('test/path/');
		
		var_dump($url->uri(), $route->getPathPattern());

		$this->assertTrue($route->test($url));
	}

	public function testRouteParameter()
	{
        $url = $this->getMockBuilder(ieu\Http\Url::CLASS)
                    ->getMock();

        $url->method('uri')
            ->willReturn('test/path/user123');

		$route = new Route('test/path/{user}');

		$this->assertTrue($route->test($url));
	}

	public function testRouteParameterValidation()
	{
        $url = $this->getMockBuilder(ieu\Http\Url::CLASS)
                    ->getMock();

        $url->method('uri')
            ->willReturn('test/path/user123');

		$route = (new Route('test/path/{user}'))
		         ->validate('user', 'user[0-9]+');

		$this->assertTrue($route->test($url));
	}
}