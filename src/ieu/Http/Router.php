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
	 * All handler routes bundles attachte to this router
	 * @var array<array<callable, array<ieu\Http\Route>>> 
	 */
	
	private $handlerAndRoutesCache = [];

	private $currentRoutes = [];

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Sets the a new current route for this handler.
	 *
	 * @param  Route  $route [description]
	 *
	 * @return [type]        [description]
	 */
	
	public function when(Route $route)
	{
		$this->currentRoutes[] = $route;

		return $this;
	}

	public function then(callable $handler)
	{
		$this->handlerAndRoutesCache[] = [$handler, $this->currentRoutes];
		$this->currentRoutes = [];

		return $this;
	}

	public function handle() 
	{
		// Loop over all handler routes bundles
		foreach ($this->handlerAndRoutesCache as $handlerAndRoutes) {
			list($handler, $routes) = $handlerAndRoutes;

			// Loop over all routes for this handler
			foreach ($routes as $route) {

				// Test for method (eg. HTTP_GET, HTTP_POST, ...)
				if (!$this->request->isMethod($route->getMethods())) {
					echo 'Wrong method';
					continue;
				}

				// Test for pathpattern
				if (!$route->parse($this->request->getUrl())) {
					continue;
				}

				return call_user_func($handler, $route->getParameterValues(), $this->request);
			}
		}

		if ($this->hasDefaultHandler()) {
			return call_user_func($this->getDefaultHandler(), $this->request);
		}

		throw new Exception('No matching route found.');
	}
}


