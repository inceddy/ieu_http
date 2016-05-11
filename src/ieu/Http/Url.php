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
use InvalidArgumentException;


/**
 * Provides an object to handle the an URL.
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 */

class Url {

	private static $portSchemeMap = [
		21  => 'ftp',
		80  => 'http',
		443 => 'https',
	];


	/**
	 * The scheme of this URL eg. `http`.
	 * @var string
	 */
	
	protected $scheme;


	/**
	 * The username of this URL or null if not provided.
	 * @var string|null
	 */
	
	protected $user;


	/**
	 * The password of this URL or null if not provided.
	 * @var string|null
	 */
	
	protected $pass;


	/**
	 * The host of the URL eg. `www.acme.com`
	 * @var string
	 */
	
	protected $host;


	/**
	 * The port of the URL eg. `443`.
	 * If no port is provided it can be determined from the port-scheme-map.
	 * @var int
	 */
	
	protected $port;


	/**
	 * The path partials.
	 * http://comp.com/a/b/c -> ['a', 'b', 'c']
	 * @var array<string>
	 */
	
	protected $partials = [];


	/**
	 * The file of this URL
	 * @var null|string
	 */
	
	protected $file = null;


	/**
	 * Constructor
	 *
	 * @param array $options the options for this instance
	 * 
	 */
	
	public function __construct($parts = [])
	{
		$parts = array_merge([
			'scheme'   => 'http',
			'host'     => 'localhost',
			'port'     => null,
			'user'     => null,
			'pass'     => null,
			'path'     => '',
			'query'    => null,
			'fragment' => null
		], $parts);

		$this->setScheme($parts['scheme']);
		$this->setUser($parts['user']);
		$this->setPassword($parts['pass']);
		$this->setHost($parts['host']);
		$this->setPort($parts['port']);
		$this->setPath($parts['path']);
	}

	/**
	 * Parses an URL string to an ieu\Http\Url object. 
	 *
	 * @throws InvalidArgumentException if $url is not a valid URL
	 *
	 * @param  string $url the URL string to parse
	 *
	 * @return ieu\Http\Url  the URL object
	 * 
	 */
	
	public static function fromUrl($url)
	{
		if (false === $parts = parse_url($url)) {
			throw new InvalidArgumentException(sprintf('%s is not a valid URL', $url));
		}

		return new static($parts);
	}

	public function setScheme($scheme)
	{
		$this->scheme = $scheme;
		return $this;
	}

	public function getScheme()
	{
		return $this->scheme;
	}

	public function setUser($user)
	{
		$this->user = $user;
		return $this;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function setPassword($password)
	{
		$this->pass = $password;
		return $this;
	}

	public function getPassword()
	{
		return $this->pass;
	}

	public function setHost($host)
	{
		$this->host = $host;
		return $this;
	}

	public function getHost()
	{
		return $this->host;
	}

	public function setPort($port)
	{
		if (null === $port) {
			$scheme = $this->getScheme();
			if (false === $port = array_search($scheme, static::$portSchemeMap)) {
				throw new InvalidArgumentException(sprintf('You must provide a port for this scheme %s', $scheme));
			}
		}

		elseif (!filter_var($port, FILTER_VALIDATE_INT)) {
			throw new InvalidArgumentException('Port must be type of integer, %s given.', gettype($port));
		}

		$this->port = (int)$port;
		return $this;
	}

	public function getPort()
	{
		return $this->port;
	}

	public function setQuery($query)
	{
		if (is_array($query)) {
			$this->query = http_build_query($query);
		}

		elseif ($query) {
			$this->query = $query;
		}

		return $this;
	}

	public function getQuery()
	{
		return $this->query;
	}


	/**
	 * Tests a regexp pattern against the URL path.
	 *
	 * @param  string $pattern the regexp patter nto test
	 *
	 * @return boolean  
	 *       
	 */
	
	public function test($pattern)
	{
		return preg_match($pattern, $this->getPath()) === 1;
	}

	public function setPath($path)
	{
		if (is_string($path)) {
			$path = trim($path, '/');

			if (false !== $pos = strpos($path, '?')) {
				$this->setQuery(substr($this->path, $pos + 1));
				$path = substr($path, 0, $pos);
			}

			$this->partials = array_filter(explode('/', $path));

			if (false !== strpos($this->last(), '.')) {
				$this->setFile(array_pop($this->partials));
			}	

			return $this;
		}


	}


	/**
	 * [getPath description]
	 *
	 * @param  boolean $withFile [description]
	 *
	 * @return [type]            [description]
	 */
	
	public function getPath($withFile = true)
	{
		return implode('/', $this->partials) . ($withFile ? '/' . $this->getFile() : ''); 
	}

	public function setFile($file)
	{
		$this->file = $file;
		return $this;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function nth($offset)
	{
		return isset($this->partials[$offset]) ? $this->partials[$offset] : null;
	}

	public function first() {
		return $this->nth(0);
	}

	public function last() {
		return end($this->partials);
	}

	public function length()
	{
		return sizeof($this->partials);
	}

	public function __toString()
	{
		$scheme = $hits->getScheme();
		// Combine user and password
		$user = $this->user ? ($this->pass ? $this->user . ':' . $this->pass . '@' : $this->user . '@') : '';
		$host = $this->getHost();
		$port = $this->getPort();
		// Don't use the port if its the standard for the current scheme
		if (isset(static::$portSchemeMap[$port]) && static::$portSchemeMap[$port] == $scheme) {
			$port = '';
		}
		$path = $this->getPath();
		$query = $this->getQuery();
		$fragment = $this->getFragment();

		return sprintf('%s://%s%s%s/%s%s%s',
			// Scheme
			$scheme,
			// User
			$user,
			// Host
			$host,
			// Port
			$port,
			// Path
			$path,
			// Query
			$query ? '?' . $query : '',
			// Fragment
			$fragment ? '#' . $fragment : ''
		);
	}

	public static function instance($options = [])
	{
		static $instances = [];
		$class = get_called_class();

		if (!isset($instances[$class])) {
			$instances[$class] = new static($options);
		}

		return $instances[$class];
	}
}