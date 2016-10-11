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

class Request {

    /**
     * The http request methods bit-encoded
     */
    
    const HTTP_ALL     = 0xff;
    const HTTP_GET     = 0x1;
    const HTTP_POST    = 0x2;
    const HTTP_HEAD    = 0x4;
    const HTTP_PUT     = 0x8;
    const HTTP_DELETE  = 0x10;
    const HTTP_TRACE   = 0x20;
    const HTTP_OPTIONS = 0x40;
    const HTTP_CONNECT = 0x80;


    /**
     * The map of all bit-encoded request methods to readable names
     * @var string[]
     */
    
    private static $httpRequestMap = [
        self::HTTP_GET     => 'GET',
        self::HTTP_POST    => 'POST',
        self::HTTP_HEAD    => 'HEAD',
        self::HTTP_PUT     => 'PUT',
        self::HTTP_DELETE  => 'DELETE',
        self::HTTP_TRACE   => 'TRACE',
        self::HTTP_OPTIONS => 'OPTIONS',
        self::HTTP_CONNECT => 'CONNECT'
    ];


    /**
     * Creates a new request with the default super global parameters of PHP.
     *
     * @param array $parameters the array with the different parameter-arrays
     *
     * @return ieu\Http\Request
     * 
     */

    public function __construct(array $parameters = [])
    {
        foreach (['get', 'post', 'files', 'cookie', 'session', 'server', 'header'] as $key) {
            $this->$key = new ParameterCollection(isset($parameters[$key]) ? $parameters[$key] : []);
        }
    }


    /**
     * Creates a new request with the default super global parameters of PHP.
     *
     * @return ieu\Http\Request
     * 
     */
    
    public static function native()
    {
        static $instance;

        if (!isset($instance)) {
            $instance = new static ([
                'get'     => $_GET, 
                'post'    => $_POST, 
                'files'   => $_FILES, 
                'cookie'  => $_COOKIE, 
                // Session must be started before!
                'session' => session_status() === PHP_SESSION_ACTIVE ? $_SESSION : [], 
                'server'  => $_SERVER, 
                // Not allways available
                'header'  => function_exists('getallheaders') ? getallheaders() : []
            ]);
        }

        return $instance;
    }


	/**
     * Shortcut to fetch a GET-parameter with optional default value  
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */
    
    public function get($key, $default = null) 
	{
        return $this->get->has($key) ? $this->get->get($key) : $default;
	}


    /**
     * Shortcut to fetch a POST-parameter with optional default value  
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */

	public function post($key, $default = null) 
	{
		return $this->post->has($key) ? $this->post->get($key) : $default;
	}


    /**
     * Shortcut to fetch a FILES-parameter with optional default value  
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */

	public function files($key, $default = null)
	{
		return $this->files->has($key) ? $this->files->get($key) : $default;
	}


    /**
     * Shortcut to fetch a SERVER-parameter with optional default value  
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */

	public function server($key, $default = null)
	{
		return $this->server->has($key) ? $this->server->get($key) : $default;
	}


    /**
     * Shortcut to fetch a COOKIE-parameter with optional default value  
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */

    public function cookie($key, $default = null)
    {
        return $this->cookie->has($key) ? $this->cookie->get($key) : $default;
    }


    /**
     * Shortcut to fetch a SESSION-parameter with optional default value.
     * The function `session_start` must be called before initializing the Request object!
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */

    public function session($key, $default = null)
    {
        return $this->session->has($key) ? $this->session->get($key) : $default;
    }


    /**
     * Shortcut to fetch the first set parameter of GET, POST or FILE with optional default value  
     *
     * @param  string $key     the key to look for
     * @param  mixed  $default the value thar will be returned if the key is not set
     *
     * @return mixed           the found or the default value
     * 
     */

	public function request($key, $default = null)
    {
		return $this->get($key) ?: $this->post($key) ?: $this->files($key) ?: $default;
	}


    /**
     * Returns the bit-code of the request method or
     * null if the method is unknown.
     * 
     * @return int|null
     * 
     */
    
    public function getMethod()
    {
        switch($this->server('REQUEST_METHOD')) {
            case 'GET':
                return self::HTTP_GET;
            case 'POST':
                return self::HTTP_POST;
            case 'HEAD':
                return self::HTTP_HEAD;
            case 'PUT':
                return self::HTTP_PUT;
            case 'DELETE':
                return self::HTTP_DELETE;
            case 'TRACE':
                return self::HTTP_TRACE;
            case 'OPTIONS':
                return self::HTTP_OPTIONS;
            case 'CONNECT':
                return self::HTTP_CONNECT;
        }

        return null;
    }


    /**
     * Returns the name of the request method or
     * 'unkown' if the method is unknown.
     * 
     * @return string
     * 
     */
    
    public function getMethodName()
    {
        return isset(self::$httpRequestMap[$this->getMethod()]) ? self::$httpRequestMap[$this->getMethod()] : 'unknown';
    }


    /**
     * Tests if the given method or methods equal the
     * current request method
     *
     * @param  integer  $method the method
     *
     * @return boolean
     * 
     */
    
    public function isMethod($method)
    {
        return $method & $this->getMethod();
    }


    /**
     * Tests if request method is POST.
     * Alias for Request::isMethod(Request::HTTP_POST).
     *
     * @return boolean
     * 
     */
    
    public function isMethodPost()
    {
        return $this->isMethod(self::HTTP_POST);
    }


    /**
     * Tests if request method is GET.
     * Alias for Request::isMethod(Request::HTTP_GET).
     *
     * @return boolean
     * 
     */

    public function isMethodGet()
    {
        return $this->isMethod(self::HTTP_GET);
    }


    /**
     * Tries to detect if the server is running behind an SSL.
     *
     * @return boolean
     */
    
    public function isBehindSsl()
    {
        // Check for proxy first
        $protocol = $this->server('HTTP_X_FORWARDED_PROTO');

        if ($protocol) {
            return $this->protocolWithActiveSsl($protocol);
        }

        $protocol = $this->server('HTTPS');

        if ($protocol) {
            return $this->protocolWithActiveSsl($protocol);
        }

        return (string) $this->server('SERVER_PORT') === '443';
    }


    /**
     * Detects an active SSL protocol value.
     *
     * @param string $protocol
     *
     * @return boolean
     * 
     */

    protected function protocolWithActiveSsl($protocol)
    {
        $protocol = strtolower((string)$protocol);
        return in_array($protocol, ['on', '1', 'https', 'ssl'], true);
    }


    /**
     * Get the currently active URL scheme.
     *
     * @return string
     */
    
    public function getHttpScheme()
    {
        return $this->isBehindSsl() ? 'https' : 'http';
    }

    public function getUser()
    {

    }


	/**
     * Tries to detect the host name of the server.
     *
     * Some elements adapted from
     *
     * @see https://github.com/symfony/HttpFoundation/blob/master/Request.php
     *
     * @return string
     * 
     */
    
    public function getHost($withPort = true)
    {
        // Check for proxy first
        if ($host = $this->server('HTTP_X_FORWARDED_HOST')) {
            $host = last(explode(',', $host));
        } elseif (!$host = $this->server('HTTP_HOST')) {
            if (!$host = $this->server('HTTP_SERVER_NAME')) {
                $host = $this->server('HTTP_SERVER_ADDR');
            }
        }

        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        if (!$withPort) {
            return $host;
        }

        // Port number
        $scheme = $this->getHttpScheme();
        $port = $this->getPort();
        
        $appendPort = ':' . $port;

        // Don't append port number if a normal port.
        if (($scheme == 'http' && $port == '80') || ($scheme == 'https' && $port == '443')) {
            $appendPort = '';
        }

        return $host . $appendPort;
    }

    /**
     * Get the port of this request as string.
     *
     * @return string
     * 
     */
    
    public function getPort()
    {
        // Check for proxy first
        $port = self::server('HTTP_X_FORWARDED_PORT');
        if ($port) {
            return (string)$port;
        }

        $protocol = (string)self::server('HTTP_X_FORWARDED_PROTO');
        if ($protocol === 'https') {
            return '443';
        }

        return (string)self::server('SERVER_PORT');
    }

    public function getUrl()
    {
        if (null === $url = $this->server('REQUEST_URI')) {
            throw new \Exception('The reuqest URI is unknown.');
        }

        return Url::fromUrl($url);
    }
}