<?php

namespace ieu\Http;
use ieu\Http\Reuqest;
use ieu\Container\Injector;

use ieu\Container\Container;

class RouterProvider {

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

	/**
	 * The handler that is called when
	 * no route matches the current request.
	 * @var array
	 */
	
	private $otherwise = null;


	/**
	 * The currend route object
	 * @var ieu\Http\Route
	 */
	
	private $currentRoute;

	/**
	 * Constructor
	 * Invokes the new router provider
	 * and sets the factory callable.
	 *
	 * @return self 
	 * 
	 */
	
	public function __construct()
	{
		$this->factory = ['Injector', 'Request', [$this, 'factory']];
	}


	/**
	 * The factory method that will be uses by the injector.
	 *
	 * @param  Injector $injector The injector
	 * @param  Request  $request  The request
	 *
	 * @return ieu\Http\Router
	 */
	
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
	 * Adds a route.
	 *
	 * @see  ieu\Http\RouterProvider::then()
	 *
	 * @param ieu\Http\Route $route
	 * 
	 * @return self
	 * 
	 */
	
	public function when(Route $route)
	{
		if (isset($this->currentRoute)) {
			$this->then([function(){}]);
		}

		$this->currentRoute = $route;

		return $this;
	}


	/**
	 * Sets the handler for the previously set route.
	 *
	 * @see  ieu\Http\RouterProvider::when()
	 *
	 * @param  callable|array $handler  The callable or the callable wrapped in a 
	 *                                  dependency array.
	 *
	 * @return self
	 * 
	 */
	
	public function then($handler)
	{
		$handler = Container::getDependencyArray($handler);

		$this->routes[] = [$this->currentRoute, $handler];
		unset($this->currentRoute);

		return $this;
	}


	/**
	 * Sets the handler that is uses if no route matches 
	 * the current request.
	 *
	 * @param  callable|array $handler  The callable or the callable wrapped in a 
	 *                                  dependency array.
	 *
	 * @return self
	 * 
	 */
	
	public function otherwise($handler) 
	{
		$handler = Container::getDependencyArray($handler);

		$this->otherwise = $handler;
		return $this;
	}
}

