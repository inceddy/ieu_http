<?php

/*
 * This file is part of ieUtilities HTTP.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace ieu\Http;
use Closure;

/**
 * A routing context frames a group of
 *  - routes
 *  - route parameter pattern
 *  - middlewares
 *  - sub contexts
 *
 * Each context (except the root context) has 
 * a parent context, a path prefix (to distinguish the context relevance
 * on the currently handled request path) and an invoker closure 
 * that sets up this context.
 *
 * The first argument of the invoker is allways the router object
 * handling the request, so that you can use all routing methods
 * from withing the invoker.
 * 
 * @author Philipp Steingrebe <development@steingrebe.de>
 */


class RoutingContext {

	/**
	 * Callable that invokes this context
	 * by adding routes, middlewares, pattern and
	 * default handlers.
	 * @var Closure
	 */
	
	private $invoker;


	/**
	 * The parent routing context
	 * @var ieu\Http\RoutingContext
	 */
	
	private $parent;


	/**
	 * The context preifx
	 * if this context has no prefix
	 * @var string
	 */
	
	private $prefix;

	/**
	 * Middlewares used by this context
	 *
	 * @var array[callable]
	 */
	
	private $middlewares = [];


	/**
	 * The routes with their handlers 
	 * controlled by this context
	 * @var array[array[ieu\Http\Route, callable]]
	 */
	
	private $routes = [];


	/**
	 * Subcontexts owned by this context
	 * @var array[ieu\Http\RoutingContext]
	 */
	
	private $contexts = [];


	/**
	 * Pattern von route parameter in this context
	 * @var array[string]
	 */
	
	private $pattern = [];


	/**
	 * The default handler for this context.
	 * These handler get's called when an error
	 * is thrown during the handling of this 
	 * context.
	 * @var callable
	 */
	
	private $default;

	public function __construct(string $prefix = null, Closure $invoker = null, RoutingContext $parent = null)
	{
		$this->prefix  = $prefix ? trim($prefix, "\n\r\t/ ") : '';

		$this->invoker = $invoker;
		$this->parent  = $parent;
	}


	/**
	 * Gets path prefix of this context and all its parents
	 *
	 * @param  string $path (optional)
	 *    The parents prefix
	 *
	 * @return string
	 *    The prefixed path
	 */
	
	public function getPrefixedPath(string $path = '') : string
	{
		return trim($this->parent ? 
			($this->parent->getPrefixedPath($this->prefix ? $this->prefix . '/' . $path : $path)) :
			($this->prefix ? $this->prefix . '/' . $path : $path), '/');
	}


	/**
	 * Adds a new parameter to this context
	 *
	 * @param string $name
	 *    The parameter name that must match this pattern
	 * @param string $pattern
	 *    The regex pattern
	 *
	 * @return void
	 */
	
	public function addPattern(string $name, string $pattern) 
	{
		$this->pattern[$name] = $pattern;
	}


	/**
	 * Gets all parameter pattern of this context
	 *
	 * @return array[string]
	 *   The pattern
	 */
	
	public function getPattern() : array
	{
		return $this->pattern;
	}


	/**
	 * Adds a new middleware to this context
	 *
	 * @param callable $middleware
	 *    The middleware
	 *
	 * @return void
	 */
	
	public function addMiddleware(callable $middleware)
	{
		$this->middlewares[] = $middleware;
	}


	/**
	 * Gets all middlewares of this context
	 * and its parent contexts
	 *
	 * @param  array[callable]  $middlewares
	 *    Middlewares to merge into this contexts middlewares
	 *
	 * @return array[callable]
	 *   The middlewares of this context and its parent contexts
	 */
	
	public function getMiddlewares(array $middlewares = []) : array
	{
		return $this->parent ? 
			$this->parent->getMiddlewares(array_merge($middlewares, $this->middlewares)) :
			array_merge($middlewares, $this->middlewares);
	}


	/**
	 * Adds a new route and its handler to this context
	 *
	 * @param ieu\Http\Route $route
	 *    The new route
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return void
	 */
	
	public function addRoute(Route $route, $handler)
	{
		$this->routes[] = [$route, $handler];
	}


	/**
	 * Gets all routes of this context
	 *
	 * @return array[ieu\Http\Route]
	 *    The routes
	 */
	
	public function getRoutes() : array
	{
		return $this->routes;
	}


	/**
	 * Adds a new sub context to this context
	 *
	 * @param string  $prefix
	 *    The path prefix of this context
	 * @param Closure $invoker
	 *    The closure invoking the new sub context
	 *
	 * @return ieu\Http\RoutingContext
	 *    The new sub context
	 */
	
	public function addSubContext(string $prefix, Closure $invoker) : RoutingContext
	{
		return $this->contexts[] = new RoutingContext($prefix, $invoker, $this);
	}


	/**
	 * Gets all sub contexts of this context
	 *
	 * @return array[ieu\Http\RoutingContext]
	 *    The sub contexts
	 */
	
	public function getSubContexts() : array
	{
		return $this->contexts;
	}


	/**
	 * Sets the default handler for this context
	 *
	 * @param callable $defaultHandler
	 *    The default handler
	 *
	 * @return void
	 */
	
	public function setDefault(callable $defaultHandler)
	{
		$this->default = $defaultHandler;
	}


	/**
	 * Gets the default handler for this context or null
	 * if none is set
	 *
	 * @return callable|null
	 *    The default handler or null
	 */
	
	public function getDefault()
	{
		return $this->default;
	}


	/**
	 * Invokes this context
	 *
	 * @param  ieu\Http\Router $router
	 *    The router invoking this context
	 *
	 * @return self
	 */
	
	public function __invoke(Router $router) : RoutingContext
	{
		if ($this->invoker) {
			($this->invoker)($router);
		}

		return $this;
	}
}
