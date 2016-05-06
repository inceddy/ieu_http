<?php

namespace ieu\Http;
use ieu\Http\Reuqest;
use ieu\Container\Injector;

class RouterProvider {

	/**
	 * The URL object of the current request
	 * @var ieu\Reuqest\Url
	 */

	private $router;

	/**
	 * The Router factory
	 * @var array
	 */
	
	public $factory;

	/**
	 * RouteCache
	 * @var array
	 */
	
	private $routeCache = [];

	public function __construct()
	{
		$this->factory = ['Injector', 'Request', [$this, 'factory']];
	}

	public function factory(Injector $injector, Request $request)
	{
		$router = new Router($request);

		foreach ($this->routeCache as $route) {
			list($route, $handler) = $route;

			if (!is_callable($handler)) {
				$handler[0] = $injector->get($handler[0] . 'Controller');
			}

			$router->addRoute($route, function() use ($injector, $route) {

			});
		}
		return $router;
	}

	/**
	 * Adds a route and the corresponding handler
	 * 
	 *
	 * @param Route $route   [description]
	 * @param array $handler [description]
	 */
	
	public function addRoute(Route $route, array $handler)
	{
		$this->routeCache[] = [$route, $handler];
		return $this;
	}
}

