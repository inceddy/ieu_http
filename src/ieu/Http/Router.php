<?php

namespace ieu\Http;
use \ieu\Container\Provider;

class Router {

	/**
	 * The URL object of the current request
	 * @var ieu\Reuqest\Url
	 */

	private $url;

	/**
	 * All routes connected to this router
	 * @var array
	 */
	
	private $routes = [];

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function addRoute(Route $route, $handler)
	{
		$this->routes[] = [$route, $handler];
		return $this;
	}

	public function handle() 
	{
		foreach ($this->routes as $route) {
			list($route, $handler) = $route;

			// Test for method
			if (!$this->request->isMethod($route->getMethods())) {
				echo 'Wrong method';
				continue;
			}

			// Test for pathpattern
			if (!$route->parse($this->request->getUri())) {
				continue;
			}

			return call_user_func($handler, $this->route->getParameter(), $this->request);
		}

		throw new \Exception('Route not found.');
	}
}


