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


	/**
	 * The injector used to resolve depedencies
	 * @var ieu\Container\Injector
	 */
	
	private $injector;

	/**
	 * Whether this provider is constructed or not
	 * @var boolean
	 */

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
		parent::__construct();
		$this->factory = ['Injector', [$this, 'factory']];
	}


	/**
	 * The factory method that will be uses by the injector.
	 *
	 * @param  Injector $injector The injector
	 *
	 * @return ieu\Http\RouterProvider
	 */
	
	public function factory(Injector $injector)
	{
		$this->injector  = $injector;
		$this->constructed = true;

		return $this;
	}


	/**
	 * Overload route to wrap route handler in a dependency array
	 *
	 * {@inheritDoc}
	 */
	
	public function route(Route $route, $handler)
	{
		return parent::route($route, function($request, $parameter) use ($handler) {
			return $this->injector->invoke(
				Container::getDependencyArray($handler), 
				['RouteParameter' => $parameter, 'Request' => $request]
			);
		});
	}


	/**
	 * Overload context to wrap invoker in a dependency array
	 *
	 * {@inheritDoc}
	 */

	public function context(string $prefix, $invoker) 
	{
		return parent::context($prefix, function() use ($invoker) {
			return $this->injector->invoke(
				Container::getDependencyArray($handler), [], [$this]
			);
		});
	}


	/**
	 * Overload otherwise to wrap default handlers in a dependency array
	 *
	 * {@inheritDoc}
	 */
	
	public function otherwise($handler)
	{
		return parent::otherwise(function($request, $error) use ($handler) {
			return $this->injector->invoke(
				Container::getDependencyArray($handler), 
				['Request' => $request, 'Error' => $error]
			);
		});
	}


	/**
	 * Overload handle to ensure that this method is 
	 * only called on an instance and not on the provider.
	 * 
	 * {@inheritDoc}
	 */
	
	public function handle(Request $request) : Response
	{
		if (!$this->constructed) {
			throw new LogicException('You cant call handle in config state.');
		}

		return parent::handle($request);
	}
}
