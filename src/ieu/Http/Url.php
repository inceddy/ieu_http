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
	 * The query
	 * @var array
	 */
	protected $query = [];


	/**
	 * The fragment
	 * @var string
	 */
	protected $fragment = '';


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
			'user'     => '',
			'pass'     => '',
			'path'     => '',
			'query'    => '',
			'fragment' => ''
		], $parts);

		$this->setScheme($parts['scheme']);
		$this->setUser($parts['user']);
		$this->setPassword($parts['pass']);
		$this->setHost($parts['host']);
		$this->setPort($parts['port']);
		$this->setPath($parts['path']);
		$this->setQuery($parts['query']);
		$this->setFragment($parts['fragment']);
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

	public function setQuery($query, $append = false)
	{
		if (is_string($query)) {
			$queryArray = [];
			parse_str($query, $queryArray);
			$query = $queryArray;
		}

		if (!is_array($query)) {
			$query = (array) $query;
		}

		$this->query = $append ? array_merge($this->query, $query) : $query;

		return $this;
	}

	public function appendQuery($query)
	{
		return $this->setQuery($query, true);
	}

	public function getQuery()
	{
		return http_build_query($this->query);
	}

	public function setFragment($fragment)
	{
		$this->fragment = $fragment ?: '';
	}

	public function getFragment()
	{
		return $this->fragment;
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


	/**
	 * Sets the URL path (file)
	 *
	 * @param string $path The path
	 *
	 * @return self
	 * 
	 */
	
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
		}

		return $this;
	}

	/**
	 * Gets the full path of the url by default with file.
	 *
	 * @param  boolean $withFile Whether to append the file or not
	 *
	 * @return string
	 * 
	 */
	
	public function getPath($withFile = true)
	{
		$partials = $this->partials;
		
		if ($withFile && $file = $this->getFile()) {
			$partials[] = $file;
		}

		return implode('/', $partials); 
	}

	public function setFile($file)
	{
		$this->file = $file;
		return $this;
	}


	/**
	 * Gets the file of the URL if one is set
	 *
	 * @return string
	 * 
	 */
	
	public function getFile()
	{
		return $this->file;
	}


	/**
	 * Gets the n'th path partial or null if the given offset is not set
	 *
	 * @param  integer $offset The offset to look for
	 *
	 * @return string|null
	 * 
	 */
	
	public function nth($offset)
	{
		return isset($this->partials[$offset]) ? $this->partials[$offset] : null;
	}


	/**
	 * Gets the first path partial
	 *
	 * @return string
	 * 
	 */
	
	public function first() {
		return $this->nth(0);
	}

	/**
	 * Gets the last path partial
	 *
	 * @return string
	 * 
	 */
	
	public function last() {
		return end($this->partials);
	}


	/**
	 * Gets the mumber of partials in the path
	 *
	 * @return integer
	 * 
	 */
	
	public function length()
	{
		return sizeof($this->partials);
	}


	/**
	 * Converts this object back to an URL string
	 *
	 * @return string
	 * 
	 */
	
	public function __toString()
	{
		$scheme = $this->getScheme();
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
}