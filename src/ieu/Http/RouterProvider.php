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
		$this->addContext();
		$this->currentContext = &$this->context[0];
		$this->factory = ['Injector', 'Request', 'Container', [$this, 'factory']];
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
		$this->request   = $request;
		$this->injector  = $injector;
		$this->constructed = true;

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
		if ($this->constructed) {
			throw new LogicException('You cant add another route if provider has been initialized!');
		}

		return parent::route($route, function($request, $parameter) use ($handler) {
			return $this->injector->invoke(
				Container::getDependencyArray($handler), 
				['RouteParameter' => $parameter, 'Request' => $request]
			);
		});
	}

	public function context($handler) {
		return parent::context(function() use ($handler) {
			return $this->injector->invoke(
				Container::getDependencyArray($handler),
				['Router' => $this]
			);
		});
	}

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
	 * @return mixed
	 */
	
	public function handle() {
		if (!$this->constructed) {
			throw new LogicException('You cant call handle in config state.');
		}

		return parent::handle();
	}


	/**
	 * This method should be used to access the container 
	 * in a context closure.
	 *
	 * @return ieu\Container\Container
	 */

	public function getContainer() :? Container
	{
		return $this->container;
	}
}
