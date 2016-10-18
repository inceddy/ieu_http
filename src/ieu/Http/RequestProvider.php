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


/**
 * The provider class for router object in an ieu\Container
 */


class RequestProvider {

	/**
	 * The Request factory
	 * @var array
	 */
	
	public $factory;

	/**
	 * The Session object
	 *
	 * @var ieu\Http\SessionInterface
	 */
	
	private $session = null;

	public function __construct()
	{
		$this->factory = [[$this, 'factory']];
	}


	/**
	 * Sets the session object to be uses by the request
	 *
	 * @param SessionInterface $session
	 *
	 * @return self
	 * 
	 */
	
	public function setSession(SessionInterface $session)
	{
		$this->session = $session;
		return $this;
	}


	/**
	 * The factory method that will be uses by the injector.
	 *
	 * @return ieu\Http\Request
	 */
	
	public function factory()
	{
		return Request::native()
			->setSession($this->session);
	}
}

