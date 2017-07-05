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
	
	protected $password;


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

	private static function parsePath(string $path) : array
	{
		return explode('/',  trim($path, "\t\n /"));
	}

	private static function parseQuery(string $query) : array
	{
		$queryArray = [];
		parse_str($query, $queryArray);

		return $queryArray;
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


	/**
	 * Constructor
	 *
	 * @param array $options the options for this instance
	 * 
	 */
	
	public function __construct($parts = [])
	{
		if (isset($parts['password'])) {
			$parts['pass'] = $parts['password'];
		}

		$parts = array_merge([
			'scheme'   => 'http',
			'host'     => 'localhost',
			'port'     => null,
			'user'     => null,
			'pass'     => null,
			'path'     => '',
			'query'    => '',
			'fragment' => null
		], $parts);

		if (empty($parts['scheme']) || empty($parts['host'])) {
			throw new InvalidArgumentException('Scheme and/or host are required parts by every url');
		}

		$this->scheme   = $parts['scheme'];
		$this->user     = $parts['user'];
		$this->password = $parts['pass'];
		$this->host     = $parts['host'];
		$this->port     = $parts['port'] ?: null;
		$this->path     = self::parsePath($parts['path']);
		$this->query    = self::parseQuery($parts['query']);
		$this->fragment = $parts['fragment'];
	}

	public function withScheme(string $scheme, $autoPort = true) : Url
	{
		$url = clone $this;

		$url->scheme = $scheme;

		if ($autoPort && $port = self::SCHEME_PORT_MAP[$scheme] ?? null) {
			$url->port = $port;
		}

		return $url;
	}

	public function scheme() : string
	{
		return $this->scheme;
	}

	public function withUser(string $user = null) : Url
	{
		if (is_string($user) && '' === trim($user)) {
			$user = null;
		}

		$url = clone $this;
		$url->user = $user;

		// Unset password when user is unset
		if (null === $user) {
			$url->password = null;
		}

		return $url;
	}

	public function user() :? string 
	{
		return $this->user;
	}

	public function withPassword(string $password = null) : Url
	{
		if (null === $this->user) {
			throw new LogicException('Can\'t set password without username. Set username first!');
		}

		if (is_string($password) && '' === trim($password)) {
			$password = null;
		}

		$url = clone $this;
		$url->pass = $password;

		return $url;
	}

	public function password() :? string
	{
		return $this->password;
	}

	public function withUserAndPassword(string $user = null, string $password = null) : Url
	{
		if (is_string($user) && '' === trim($user)) {
			$user = null;
		}

		if (null === $user || (is_string($password) && '' === trim($password))) {
			$password = null;
		}

		$url = clone $this;
		$url->user = $user;
		$url->password = $password;

		return $url;
	}

	public function withHost(string $host = null) : Url
	{
		$url = clone $this;
		$url->host = $host;

		return $url;
	}

	public function host() :? string
	{
		return $this->host;
	}

	public function withPort(int $port = null, bool $autoScheme = true) : Url
	{
		$url = clone $this;

		if (null === $port && $autoScheme) {
			$scheme = $this->scheme();
			if (false === $port = array_search($scheme, self::PORT_SCHEME_MAP)) {
				$port = null;
			}
		}

		$url->port = $port;

		return $url;
	}

	public function port() :? int
	{
		return $this->port;
	}


	/**
	 * Sets the URL path (file)
	 *
	 * @param string $path The path
	 *
	 * @return self
	 * 
	 */
	
	public function withPath($path)
	{
		$url = clone $this;

		if (is_string($path)) {
			$url->path = self::parsePath($path);
			return $url;
		}

		if (is_array($path)) {
			$url->path = array_values($path);
			return $url;
		}

		throw new InvalidArgumentException(sprintf(
			'Can\'t resolve path from given argument of type %s, use string or array instead.',
			is_object($query) ? get_class($query) : gettype($query)
		));
	}

	public function withPathPrepend($path)
	{
		$url = clone $this;

		if (is_string($path)) {
			$url->path = array_merge(self::parsePath($path), $url->path);
			return $url;
		}

		if (is_array($path)) {
			$url->path = array_merge(array_values($path), $url->path);
			return $url;
		}

		throw new InvalidArgumentException(sprintf(
			'Can\'t resolve path from given argument of type %s, use string or array instead.',
			is_object($query) ? get_class($query) : gettype($query)
		));
	}

	public function withPathAppend($path)
	{
		$url = clone $this;

		if (is_string($path)) {
			$url->path = array_merge($url->path, self::parsePath($path));
			return $url;
		}

		if (is_array($path)) {
			$url->path = array_merge($url->path, array_values($path));
			return $url;
		}

		throw new InvalidArgumentException(sprintf(
			'Can\'t resolve path from given argument of type %s, use string or array instead.',
			is_object($query) ? get_class($query) : gettype($query)
		));
	}

	/**
	 * Gets the full path of the url by default with file.
	 *
	 * @param  boolean $withFile Whether to append the file or not
	 *
	 * @return string
	 * 
	 */
	
	public function path() : string
	{
		return implode('/', $this->path); 
	}

	public function pathArray() : array
	{
		return $this->path;
	}


	/**
	 * Gets the n'th path partial or null if the given offset is not set
	 *
	 * @param  integer $offset The offset to look for
	 *
	 * @return string|null
	 * 
	 */
	
	public function nth(int $offset) :? string
	{
		return $this->path[$offset] ?? null;
	}


	/**
	 * Gets the first path partial
	 *
	 * @return string
	 * 
	 */
	
	public function first() :? string
	{
		return $this->nth(0);
	}

	/**
	 * Gets the last path partial
	 *
	 * @return string|null
	 * 
	 */
	
	public function last() :? string
	{
		return empty($this->path) ? null : end($this->path);
	}


	/**
	 * Gets the mumber of partials in the path
	 *
	 * @return integer
	 * 
	 */
	
	public function length() : int
	{
		return sizeof($this->path);
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
		return preg_match($pattern, $this->path()) === 1;
	}


	public function withQueryString(string $query, bool $merge = false) : Url
	{
		return $this->withQueryArray(
			self::parseQuery($query)
		);
	}

	public function withQueryArray(array $query, bool $merge = false) : Url
	{
			$url = clone $this;
			$url->query = $merge ? array_merge($this->query, $query) : $query;

			return $url;
	}

	public function withQuery($query, bool $merge = false) : Url
	{
		if (is_string($query)) {
			return $this->withQueryString($query, $merge);
		}

		if (is_array($query)) {
			return $this->withQueryArray($query, $merge);
		}

		throw new InvalidArgumentException(sprintf(
			'Can\'t resolve query from given argument of type %s, use string or array instead.',
			is_object($query) ? get_class($query) : gettype($query)
		));
	}

	public function withMergedQuery($query) : Url
	{
		return $this->withQuery($query, true);
	}

	public function queryArray() : array
	{
		return $this->query;
	}

	public function query()
	{
		return http_build_query($this->query);
	}

	public function withFragment(string $fragment = null) : Url
	{
		$url = clone $this;
		$url->fragment = $fragment;

		return $url;
	}

	public function fragment()
	{
		return $this->fragment;
	}


	public function toString(bool $pathOnly = false)
	{
		$path = rtrim(sprintf('%s?%s#%s', 
			$this->path(), 
			$this->query(), 
			$this->fragment()), '?#');

		if ($pathOnly) {
			return $path;
		}

		$scheme = $this->scheme();

		// Combine user and password
		$credentials = $this->user ? ($this->password ? $this->user . ':' . $this->password . '@' : $this->user . '@') : '';
		$host = $this->host();
		$port = $this->port();
		// Don't use the port if its the standard for the current scheme
		if ($port !== null && isset(self::PORT_SCHEME_MAP[$port]) && self::PORT_SCHEME_MAP[$port] == $scheme) {
			$port = null;
		}

		return rtrim(sprintf('%s://%s%s%s/%s',
			// Scheme
			$scheme,
			// Credentials
			$credentials,
			// Host
			$host,
			// Port
			is_null($port) ? '' : ':' . $port ,
			// Path
			$path
		), '/');
	}


	/**
	 * Converts this object back to an URL string
	 *
	 * @return string
	 * 
	 */
	
	public function __toString()
	{
		return $this->toString();
	}
}