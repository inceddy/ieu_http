<?php

/*
 * This file is part of ieUtilities HTTP.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ieu\Http;
use ieu\Http\Reuqest;
use ieu\Container\Injector;
use ieu\Container\Container;


/**
 * The provider class for router object in an ieu\Container
 */


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
	
	private $otherwise;


	/**
	 * The currend route objects
	 * @var array<ieu\Http\Route>
	 */
	
	private $currentRoutes = [];


	/**
	 * Variable pattern that are valid
	 * for all Routes.
	 *
	 * @var [string]
	 */
	
	private $globalPattern = [];

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

		// Set Routes
		foreach ($this->routes as $route) {
			list($routes, $handler) = $route;

			foreach ($routes as $route) {
				$router->when($route);
			}

			$router->then(function($parameter, $request) use ($injector, $handler) {
				return $injector->invoke($handler, ['RouteParameter' => $parameter, 'Request' => $request]);
			});
		}

		// Set global pattern
		foreach ($this->globalPattern as $name => $pattern) {
			$router->validate($name, $pattern);
		}

		// Set default handler
		if (isset($this->otherwise)) {
			$handler = $this->otherwise;
			$router->otherwise(function($request, $error) use ($injector, $handler) {
				return $injector->invoke($handler, ['Request' => $request, 'Error' => $error]);
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
		$this->currentRoutes[] = $route;

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
		// Wrap handler in dependency array if nessesary
		$handler = Container::getDependencyArray($handler);

		$this->routes[] = [$this->currentRoutes, $handler];
		unset($this->currentRoutes);

		return $this;
	}


	/**
	 * Sets a global variable pattern for the
	 * given name.
	 *
	 * @param  string $name
	 *     The variable name to validate
	 * @param  string $pattern
	 *     The pattern the variable must match
	 *
	 * @return self
	 */
	
	public function validate($name, $pattern)
	{
		$this->globalPattern[$name] = $pattern;
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

