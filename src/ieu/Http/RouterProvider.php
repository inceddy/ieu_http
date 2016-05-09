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
	
	private $routes = [];

	private $otherwise = null;

	private $currentRoute;

	public function __construct()
	{
		$this->factory = ['Injector', 'Request', [$this, 'factory']];
	}

	public function factory(Injector $injector, Request $request)
	{
		$router = new Router($request);

		foreach ($this->routes as $route) {
			list($route, $handler) = $route;

			$router->addRoute($route, function($parameter, $request) use ($injector, $handler) {
				return $injector->invoke($handler, ['RouteParameter' => $parameter, 'Request' => $request]);
			});
		}

		return $router;
	}

	/**
	 * Adds a route and the corresponding handler
	 * 
	 *
	 * @param Route $route   [description]
	 * 
	 * @return self
	 */
	
	public function when(Route $route)
	{
		if (isset($this->currentRoute)) {
			$this->then([function(){}]);
		}

		$this->currentRoute = $route;

		return $this;
	}

	public function then($handler)
	{
		$this->routes[] = [$this->currentRoute, $handler];
		unset($this->currentRoute);

		return $this;
	}

	public function otherwise($handler) 
	{
		$this->otherwise = $handler;

		return $this;
	}
}

