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

	private const PORT_SCHEME_MAP = [
		21  => 'ftp',
		80  => 'http',
		443 => 'https',
	];

	private const SCHEME_PORT_MAP = [
		'ftp'   => 21,
		'http'  => 80,
		'https' => 443
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

		if (empty($parts['scheme']) || empty($parts['host'])) {
			throw new InvalidArgumentException('Scheme and/or host are required parts by every url');
		}

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
	 * Deprecated. 
	 * Use Url::from instead.
	 * 
	 * @codeCoverageIgnore
	 */
	public static function fromUrl($url)
	{
		trigger_error('Please use Url::from', E_USER_DEPRECATED);
		return static::from($url);	
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

	public static function from(string $url)
	{
		if ((false === $parts = parse_url($url)) || 
			  0 === preg_match('@^[a-z0-9-_]+://(-\.)?([^\s/?\.#]+\.?)+(/[^\s]*)?$@i', $url)) {
			throw new InvalidArgumentException(sprintf('%s is not a valid URL', $url));
		}

		return new static($parts);
	}

	public function setScheme(string $scheme, $autoPort = true)
	{
		$this->scheme = $scheme;

		if ($autoPort && $port = self::SCHEME_PORT_MAP[$scheme] ?? null) {
			$this->setPort($port);
		}

		return $this;
	}

	public function getScheme() : string
	{
		return $this->scheme;
	}

	public function setUser(string $user = null)
	{
		$this->user = $user;

		// Unset password when user is unset
		if (null === $user) {
			$this->setPassword(null);
		}

		return $this;
	}

	public function getUser() :? string 
	{
		return $this->user;
	}

	public function setPassword(string $password = null) :? string
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

	public function setPort(int $port = null, $autoScheme = true)
	{
		if (null === $port && $autoScheme) {
			$scheme = $this->getScheme();
			if (false === $port = array_search($scheme, self::PORT_SCHEME_MAP)) {
				$port = null;
			}
		}

		$this->port = $port;
		return $this;
	}

	public function getPort() :? int
	{
		return $this->port;
	}

	public function setQuery($query, bool $append = false)
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
	
	public function test(string $pattern) : bool
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
				$this->setQuery(substr($path, $pos + 1));
				$path = substr($path, 0, $pos);
			}

			$partials = array_filter(explode('/', $path));

			if (false !== strpos(end($partials), '.')) {
				$this->setFile(array_pop($partials));
			}	
		}

		$this->partials = $partials;

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
	
	public function getPath(bool $withFile = true) : string
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
		$credentials = $this->user ? ($this->pass ? $this->user . ':' . $this->pass . '@' : $this->user . '@') : '';
		$host = $this->getHost();
		$port = $this->getPort();
		// Don't use the port if its the standard for the current scheme
		if ($port !== null && isset(self::PORT_SCHEME_MAP[$port]) && self::PORT_SCHEME_MAP[$port] == $scheme) {
			$port = null;
		}
		$path = $this->getPath();
		$query = $this->getQuery();
		$fragment = $this->getFragment();

		return rtrim(sprintf('%s://%s%s%s/%s%s%s',
			// Scheme
			$scheme,
			// Credentials
			$credentials,
			// Host
			$host,
			// Port
			is_null($port) ? '' : ':' . $port ,
			// Path
			$path,
			// Query
			$query ? '?' . $query : '',
			// Fragment
			$fragment ? '#' . $fragment : ''
		), '/');
	}
}