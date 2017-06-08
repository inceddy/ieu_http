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

/**
 * Route describing an url path
 * e.g. /test/{user_id}/edit
 * 
 * @author Philipp Steingrebe <development@steingrebe.de>
 */


class Route {

	// user/{id[0-9+]?}
	private const VAR_PATTERN = '/\{([a-z0-9\-_]+)(?:|([^\}]+))?\}/i';
	private const ALLOWED_CHARS = '[a-z0-9_\.~\-]+';


	/**
	 * The route description
	 * @var string
	 */
	
	protected $route;


	/**
	 * The request methods handled by this route
	 * @see ieu\Request\Request
	 * @var integer
	 */
	
	protected $methods;


	/**
	 * Names of all parameters found in route description
	 * @var array<string>
	 */
	
	protected $parameter = [];


	/**
	 * Validierunsmuster für Variablenwerte
	 * @var array
	 */
	
	protected $parameterPattern = [];


	/**
	 * Indicates if the route pattern is terminated with '$'.
	 * @var boolean
	 */
	
	protected $terminated = true;


	/**
	 * Constructor
	 *
	 * @param string $route
	 *    The route description
	 * @param int $methods
	 *    The http method bitmask
	 */
	
	public function __construct(string $route, int $methods = Request::HTTP_ALL)
	{
		$route = trim($route, " \t\n\r/"); // Remove leading and tailing slashes and whitespaces

		if (substr($route, -1) == '*') {
			$route = substr($route, 0, -1);
			$this->setTermination(false);
		}

		$this->route = $route;
		$this->methods = $methods;
	}

	/**
	 * Builds the pattern a variable in the url path 
	 * must match.
	 *
	 * @param  string $key
	 *    The parameter key
	 *
	 * @return string
	 *    The generated pattern
	 */
	
	protected function buildParameterPattern(string $key) : string
	{
		return '(' . str_replace('~', '\\~', $this->parameterPattern[$key] ?? self::ALLOWED_CHARS) . ')';
	}


	/**
	 * Builds the pattern an url path must match
	 * to be handled by this route.
	 *
	 * @return string
	 */
	
	protected function buildRoutePattern()
	{
		return '~^' . preg_replace_callback(self::VAR_PATTERN, function($matches) {
			$this->parameter[] = $key = $matches[1];

			// Shorthand validation only if not set using the validation-method
			if (isset($matches[2]) && !isset($this->parameterPattern[$key])) {
				$this->parameterPattern[$key] = $matches[2];
			}
			return $this->buildParameterPattern($key);
		}, $this->route) . ($this->terminated ? '$' : '') . '~i';

	}


	/**
	 * Set whether this route is terminated or not
	 *
	 * @param bool $terminated
	 *
	 * @return self
	 */
	
	public function setTermination(bool $terminated = true)
	{
		$this->terminated = $terminated;
		return $this;
	}


	/**
	 * Gets whether or not this route is terminated or not
	 *
	 * @return bool
	 */
	
	public function getTermination() : bool
	{
		return $this->terminated;
	}


	/**
	 * Trys to extract all route variables of a given Url.
	 * When the route doesen match the Url `null` will be returned.
	 *
	 * @param ieu\Http\Url $url 
	 *    The Url to parse
	 *
	 * @return array<string>|null
	 *    The array of route parameters or null if not matching
	 */
	
	public function parse(Url $url) :? array
	{
		// Get path without possible file
		$path = $url->getPath(false);

		// Build route pattern
		$pattern = $this->buildRoutePattern();

		if (preg_match($pattern, $path, $matches) === 1) {

			$variables = [];
			$length = sizeof($matches);

			for ($i = 1; $i < $length; $i++) {
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


	/**
	 * Returns the valid HTTP request methods for this route
	 * in binary decoded.
	 *
	 * @return integer
	 *    The valid methods
	 */
	
	public function getMethods()
	{
		return $this->methods;
	}

	public function __toString()
	{
		return $this->route;
	}
}