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

/**
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 */


class Route {

	const VAR_PATTERN = '/\{([a-z0-9\-_]+)\}/i';

	/**
	 * Die Request-Methode, die diese Route bedient
	 * @see ieu\Request\Request
	 * @var integer
	 */
	
	protected $methods;


	/**
	 * Der Pfad dieser Route
	 * @var string
	 */
	
	protected $path;


	/**
	 * Validierunsmuster für Variablenwerte
	 * @var array
	 */
	
	protected $parameterPattern = [];


	/**
	 * Validierungsmuster für den Pfad
	 * @var string
	 */
	
	protected $routePattern;	

	/**
	 * Indicates if the route pattern is terminated with '$'.
	 * @var boolean
	 */
	
	protected $terminated = true;


	/**
	 * Die Parameter 
	 *
	 * @var array
	 */
	
	protected $parameter = [];


	public function __construct($route, $methods = Request::HTTP_ALL)
	{
		$route = trim($route, " \t\n\r/"); // Remove leading and tailing slashes and whitespaces

		if (substr($route, -1) == '*') {
			$route = substr($route, 0, -1);
			$this->terminated(false);
		}

		$this->route = $route;
		$this->methods = $methods;
	}

	public function getRoutePattern()
	{
		if (isset($this->routePattern)) {
			return $this->routePattern;
		}

		$this->routePattern = '~^' . preg_replace_callback(self::VAR_PATTERN, function($matches) {
			$this->parameter[] = $key = $matches[1];
			return '(' . (isset($this->parameterPattern[$key]) ? $this->parameterPattern[$key] : '\w+') . ')';
		}, $this->route) . ($this->terminated ? '$' : '') . '~i';

		return $this->routePattern;
	}

	public function setTermination($terminated = true)
	{
		$this->terminated = $terminated;
		return $this;
	}

	public function getTermination()
	{
		return $this->terminated;
	}


	public function parse(Url $url)
	{
		$path = rtrim($url->getPath(), '/');

		if (preg_match($this->getRoutePattern(), $path, $matches) === 1) {
			$variables = [];
			
			for ($i = 1; $i < sizeof($matches); $i++) {
				$variables[$this->parameter[$i - 1]] = $matches[$i];
			}

			return $variables;
		}

		return null;
	}


	/**
	 * Setzt ein Validierungsmuster für einen
	 * Variablen-Schlüssel
	 *
	 * @param  string $key      der Schlüssel der Variablen
	 * @param  string $pattern  das Muster für den Variablenwert
	 *
	 * @return self
	 * 
	 */
	
	public  function validate($key, $pattern)
	{
		$this->parameterPattern[$key] = $pattern;
		return $this;
	}

	public function getMethods()
	{
		return $this->methods;
	}
}