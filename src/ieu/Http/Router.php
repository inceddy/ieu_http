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
use Closure;

/**
 * Class for routing http requests
 */

class Router {

	private const ROUTES            = 0;
	private const PREFIX            = 1;
	private const MIDDLEWARE        = 2;
	private const DEFAULT           = 3;
	private const PARAMETER_PATTERN = 4;

	/**
	 * The current request handled by this router
	 * @var ieu\Reuqest
	 */

	private $request;


	/**
	 * All handler routes bundles attachte to this router
	 * @var array<array<callable, array<ieu\Http\Route>>> 
	 */


	/**
	 * Router context
	 * @var array
	 */

	private $context = [];


	/**
	 * Reference on the current context
	 * @var array
	 */
	
	private $currentContext;


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
		$this->currentContext = &$this->addContext();
	}

	private function &addContext()
	{
		$this->context[] = [
			self::ROUTES            => [],
			self::MIDDLEWARE        => [],
			self::PREFIX            => null,
			self::DEFAULT           => null,
			self::PARAMETER_PATTERN => []
		];

		return $this->context[sizeof($this->context) - 1];
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

	public function context(Closure $handler) {
		// Add new group context
		$this->currentContext = &$this->addContext();
		Closure::bind($handler, $this)();
		// Reset to default context
		$this->currentContext = &$this->context[sizeof($this->context) - 1];

		return $this;
	}

	public function middleware(callable ...$middlewares)
	{
		$this->currentContext[self::MIDDLEWARE] = array_merge($this->currentContext[self::MIDDLEWARE], $middlewares);
		return $this;
	}

	/**
	 * Add path prefix to current context
	 * e.g. `custom/prefix
	 *
	 * @param  string $prefix 
	 *    The path prefix
	 *
	 * @return self
	 */
	
	public function prefix(string $prefix) 
	{
		$this->currentContext[self::PREFIX] = trim($prefix, "/\n\t ");
		return $this;
	}


	/**
	 * Add new route to current context
	 *
	 * @param  Route  $route 
	 *    The route to add
	 *
	 * @return self
	 */
	
	public function route(Route $route, callable $handler) 
	{
		$this->currentContext[self::ROUTES][] = [$route, $handler];
		return $this;
	}


	public function request(string $path, int $methods = Request::HTTP_ALL, callable $handler)
	{
		$path = trim($path, "/\n\t ");

		if (null !== $prefix = $this->currentContext[self::PREFIX]) {
			$path = $prefix . '/' . $path;
		}

		return $this->route(new Route($path, $methods), $handler);
	}

	public function get(string $path, callable $handler)
	{
		return $this->request($path, Request::HTTP_GET | Request::HTTP_HEAD, $handler);
	}

	public function post(string $path, callable $handler)
	{
		return $this->request($path, Request::HTTP_POST, $handler);
	}

	public function put(string $path, callable $handler)
	{
		return $this->request($path, Request::HTTP_PUT, $handler);
	}

	public function delete(string $path, callable $handler)
	{
		return $this->request($path, Request::HTTP_DELETE, $handler);
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
		$this->currentContext[self::PARAMETER_PATTERN][$name] = $pattern;
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
		$this->currentContext[self::DEFAULT] = $defaultHandler;
		return $this;
	}

	private function composeMiddleware(array $middlewares)
	{
		$curryedMiddlewares = array_map(function($middleware) {
			return function ($next) use ($middleware) {
				return function (...$args) use ($middleware, $next) {
					return call_user_func($middleware, $next, ...$args);
				};
			};
		}, array_reverse($middlewares));

		return function ($handler) use ($curryedMiddlewares) {
			$handler = function(...$args) use ($handler) {
				return call_user_func_array($handler, $args);
			};
			return array_reduce($curryedMiddlewares, function($state, $middleware){
				return function(...$args) use($state, $middleware) {
					return $middleware($state)(...$args);
				};
			}, $handler);
		};
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

		// Loop over all context
		foreach ($this->context as $context) {
			foreach ($context[self::ROUTES] as $routeAndHandler) {
				list($route, $handler) = $routeAndHandler;

				// Test for method (eg. HTTP_GET, HTTP_POST, ...)
				if (!$this->request->isMethod($route->getMethods())) {
					continue;
				}


				// Test for local pattern
				if (null === $parameter = $route->parse($this->request->getUrl())) {
					continue;
				}

				// Test for global pattern
				foreach (array_intersect_key($context[self::PARAMETER_PATTERN], $parameter) as $name => $pattern) {
					if (0 === preg_match($pattern , $parameter[$name])) {
						continue 2;
					}
				}
				
				// Execute route handler
				try {
					$result = $this->composeMiddleware($context[self::MIDDLEWARE])($handler)($this->request, $parameter);

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

			// Use context default handler
			if (isset($context[self::DEFAULT])) {
				return call_user_func($context[self::DEFAULT], $this->request, $error);
			}
		}

		// Use default context default handler
		if (isset($this->context[0][self::DEFAULT])) {
			return call_user_func($this->context[0][self::DEFAULT], $this->request, $error);
		}

		if (null !== $error) {
			throw $error;
		}

		throw new Exception('No matching route found. Set a default handler to catch this case.');
	}
}