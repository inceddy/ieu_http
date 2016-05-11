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

use ieu\Container\Provider;
use LogicException;
use Exception;

/**
 * Class for routing http request
 */

class Router {

	/**
	 * The current request handled by this router
	 * @var ieu\Reuqest
	 */

	private $request;


	/**
	 * All handler routes bundles attachte to this router
	 * @var array<array<callable, array<ieu\Http\Route>>> 
	 */
	
	private $handlerAndRoutesCache = [];


	/**
	 * The route cache for the next occuring handler
	 * @var array<ieu\Http\Route>
	 */
	
	private $currentRoutes = [];


	/**
	 * The default handler if no route matches the request
	 * @var callable
	 */
	
	private $defaultHandler;


	/**
	 * Constructor
	 * Invokes a new router object
	 *
	 * @param Request $request  The request handled by this router
	 *
	 * @return self
	 * 
	 */
	
	public function __construct(Request $request)
	{
		$this->request = $request;
	}


	/**
	 * Sets the request which is handled by this router
	 *
	 * @param Request $request  The request to handle
	 *
	 * @return self
	 * 
	 */
	
	public function setRequest(Request $request)
	{
		$this->request = $request;

		return $this;
	}


	/**
	 * Gets the request handled by this router
	 *
	 * @return ieu\Http\Request  The request
	 * 
	 */
	
	public function getRequest()
	{
		return $this->request;
	}


	/**
	 * Adds a new current route for the next comming handler.
	 *
	 * @param  Route  $route  The route
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
	 * Sets the handler for the cached routes.
	 *
	 * @throws LogicException  if there is no route set, that might be handled
	 *
	 * @param  callable $handler The handler
	 *
	 * @return self
	 * 
	 */
	
	public function then(callable $handler)
	{
		if (empty($this->currentRoutes)) {
			throw new LogicException(sprintf("Nothing to handle. The route cache is empty. Use %s::when() to set a route.", __CLASS__));
		}

		$this->handlerAndRoutesCache[] = [$handler, $this->currentRoutes];
		$this->currentRoutes = [];

		return $this;
	}


	/**
	 * Trys to match a route against the current request.
	 *
	 * @throws Exception  if no route matches the request
	 *
	 * @return mixed The response of the matching handler
	 * 
	 */
	
	public function handle() 
	{
		// Loop over all handler routes bundles
		foreach ($this->handlerAndRoutesCache as $handlerAndRoutes) {
			list($handler, $routes) = $handlerAndRoutes;

			// Loop over all routes for this handler
			foreach ($routes as $route) {

				// Test for method (eg. HTTP_GET, HTTP_POST, ...)
				if (!$this->request->isMethod($route->getMethods())) {
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