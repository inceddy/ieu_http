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
use LogicException;


/**
 * The provider class for router object in an ieu\Container
 */


class RouterProvider extends Router {

	/**
	 * The Router factory
	 * @var array
	 */
	
	public $factory;

	private $constructed = false;

	

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
		foreach ($this->context as &$context) {
			if (isset($context[self::DEFAULT])) {
				$context[self::DEFAULT] = function($request, $error) use ($injector) {

				};
			}
			
			array_walk($context[self::ROUTES], function(&$routeAndHandler) use ($injector) {
				list(, $handler) = $routeAndHandler;
				$routeAndHandler[1] = function($request, $parameter) use ($injector, $handler) {
					return $injector->invoke($handler, ['RouteParameter' => $parameter, 'Request' => $request]);
				};
			});
		}

		parent::__construct($request);
		$this->constructed = true;

		return $this;
	}

	public function route(Route $route, callable $handler)
	{
		if ($this->constructed) {
			throw new LogicException('You cant add another route if provider has been initialized!');
		}

		return parent::route($route, $handler);
	}

	public function handle(Url $url) {
		if (!$this->constructed) {
			throw new LogicException('You cant call handle an the RouteProvider!');
		}

		return parent::handle($url);
	}
}

