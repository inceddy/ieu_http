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
 * Class for routing http requests
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
	 * Variable pattern that are valid
	 * for all Routes.
	 *
	 * @var [string]
	 */
	
	private $globalPattern = [];
	


	/**
	 * Constructor
	 * Invokes a new router object
	 *
	 * @param Request $request  
	 *    The request handled by this router
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
	 * @param  Route  $route  
	 *    The route
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
	 * @throws \LogicException
	 *    If there is no route set, that might be handled
	 *
	 * @param  callable $handler 
	 *    The handler
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
	 * Sets a default handler that is uses if no
	 * route matches the request.
	 *
	 * @param  callable $defaultHandler 
	 *    The handler
	 *
	 * @return self
	 * 
	 */
	
	public function otherwise(callable $defaultHandler)
	{
		$this->defaultHandler = $defaultHandler;

		return $this;
	}


	/**
	 * Gets the default handler or null of no default handler is set.
	 *
	 * @return callable|null  
	 *    The handler
	 */
	
	public function getDefaultHandler()
	{
		return isset($this->defaultHandler) ? $this->defaultHandler : null;
	}


	/**
	 * Trys to match a route against the current request.
	 *
	 * @throws \Exception
	 *    If no route matches the request
	 *
	 * @return \ieu\Http\Response
	 *    The response of the matching handler
	 * 
	 */
	
	public function handle() 
	{
		// Exception caught while handling the routes
		$error = null;

		// Loop over all handler routes bundles
		foreach ($this->handlerAndRoutesCache as $handlerAndRoutes) {
			list($handler, $routes) = $handlerAndRoutes;

			// Loop over all routes for this handler
			foreach ($routes as $route) {

				// Test for method (eg. HTTP_GET, HTTP_POST, ...)
				if (!$this->request->isMethod($route->getMethods())) {
					continue;
				}

				// Test for local pattern
				if (null === $parameter = $route->parse($this->request->getUrl())) {
					continue;
				}

				// Test for global pattern
				foreach (array_intersect_key($this->globalPattern, $parameter) as $name => $pattern) {
					if (0 === preg_match($pattern , $parameter[$name])) {
						continue 2;
					}
				}
					

				try {
					$result = call_user_func($handler, $parameter, $this->request);

					switch (true) {
						// Nothing returned -> continue
						case is_null($result):
							continue;
						// Response object
						case $result instanceof Response:
							return $result;
						// String -> transform to response
						case is_string($result) || is_numeric($result):
							return new Response($result);
						// Array -> transform to json response
						case is_array($result) || is_object($result):
							return new JsonResponse($result);

						default:
							throw new Exception('Invalid route return value');
					}
				} catch(Exception $e) {
					$error = $e;
					break 2;
				}
			}
		}

		if (isset($this->defaultHandler)) {
			return call_user_func($this->getDefaultHandler(), $this->request, $error);
		}

		if (null !== $error) {
			throw $error;
		}

		throw new Exception('No matching route found. Set a default handler to catch this case.');
	}

	public static function sanitizePattern($pattern)
	{
		
	}
}