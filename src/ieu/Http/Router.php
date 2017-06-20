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

	protected const ROUTES            = 0;
	protected const PREFIX            = 1;
	protected const MIDDLEWARE        = 2;
	protected const DEFAULT           = 3;
	protected const PARAMETER_PATTERN = 4;
	protected const CONTEXT_HANDLER   = 5;

	/**
	 * The current request handled by this router
	 * @var ieu\Reuqest
	 */

	protected $request;


	/**
	 * All handler routes bundles attachte to this router
	 * @var array<array<callable, array<ieu\Http\Route>>> 
	 */


	/**
	 * Router context
	 * @var array
	 */

	protected $context = [];


	/**
	 * Reference on the current context
	 * @var array
	 */
	
	protected $currentContext;


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
		$this->addContext();
		$this->currentContext = &$this->context[0];
	}

	protected function addContext(Closure $handler = null)
	{
		$this->context[] = [
			self::ROUTES            => [],
			self::MIDDLEWARE        => [],
			self::PREFIX            => null,
			self::DEFAULT           => null,
			self::PARAMETER_PATTERN => [],
			self::CONTEXT_HANDLER   => $handler
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

	public function context($handler) {
		if (!$handler instanceof Closure) {
			throw new InvalidArgumentException('Context handler must be instance of Closure.');
		}

		// Add new group context
		$this->addContext(Closure::bind($handler, $this));
		return $this;
	}

	public function middleware(...$middlewares)
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
	
	public function route(Route $route, $handler) 
	{
		if (!is_callable($handler)) {
			throw new Exception('Route handler must be callable.');
		}

		$this->currentContext[self::ROUTES][] = [$route, $handler];
		return $this;
	}


	public function request(string $path, int $methods, $handler)
	{
		$path = trim($path, "/\n\t ");

		if (null !== $prefix = $this->currentContext[self::PREFIX]) {
			$path = $prefix . '/' . $path;
		}

		return $this->route(new Route($path, $methods), $handler);
	}

	public function get(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_GET | Request::HTTP_HEAD, $handler);
	}

	public function post(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_POST, $handler);
	}

	public function put(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_PUT, $handler);
	}

	public function delete(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_DELETE, $handler);
	}

	public function any(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_ALL, $handler);
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
	
	public function otherwise($defaultHandler)
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
	 * Transforms any primitive handler result to a response
	 *
	 * @param  mixed $result
	 *    The handler result to be transformed
	 *
	 * @return ieu\Http\Response
	 *    The response
	 */
	
	private function resultToResponse($result)
	{
		switch (true) {
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
				throw new Exception('Invalid route handler return value');
		}
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
		foreach ($this->context as &$context) {
			$this->currentContext = &$context;

			// Invoke context
			if (null !== $invoker = $context[self::CONTEXT_HANDLER]) {
				$invoker();
			}

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

					// Call next handler if `null` was returned
					if (null === $result) {
						continue;
					}

					return $this->resultToResponse($result);

				} catch(Exception $e) {
					$error = $e;
					break 2;
				}
			}

			// Use context default handler
			if (isset($context[self::DEFAULT])) {
				if (null === $result = call_user_func($context[self::DEFAULT], $this->request, $error)) {
					continue;
				}

				return $this->resultToResponse($result);
			}
		}

		// Use default context default handler
		if (isset($this->context[0][self::DEFAULT])) {
			return $this->resultToResponse(
				call_user_func($this->context[0][self::DEFAULT], $this->request, $error)
			);
		}

		// If no handler takes
		if (null !== $error) {
			throw $error;
		}

		throw new Exception('No matching route found. Set a default handler to catch this case.');
	}
}
