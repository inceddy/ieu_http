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
use Countable;
use IteratorAggregate;
use ArrayIterator;

class CookieCollection implements CookieCollectionInterface, Countable, IteratorAggregate
{
    private $options = [
        'secure'   => false,
        'httponly' => false,
        'expire'   => 0,
        'domain'   => null,
        'path'     => '/'
    ];

    private $started;


    /**
     * Constructor
     *
     * @param array $options
     *    The default options for this cookie collection.
     *    These options are used in the CookieCollection::set method.
     */
    
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }


    /**
     * Returns if cookie with the given key is set.
     *
     * @param  string  $key
     *    The key/name of the cookie
     *
     * @return boolean
     *    Whether the cookie with the given key/name exists or not
     */
    
    public function has($key)
    {
        return isset($_COOKIE[$key]);
    }


    /**
     * Returns the value of the cookie with the given key/name
     * or NULL if the cookie is not set.
     *
     * @param  string  $key
     *    The key/name of the cookie
     *
     * @return string
     *    The cookie value
     */
    
    public function get($key) 
    {
        return $this->has($key) ? $_COOKIE[$key] : null;
    }


    /**
     * Sets the value of a cookie.
     *
     * @param  string  $key
     *    The key/name of the cookie
     * @param mixed $value
     *    The value which will be casted to a string
     * @param array  $options
     *    The options to use for this cookie.
     *
     * @return self
     */
   
    public function set($key, $value, array $options = [])
    {
        $value = (string)$value;
        $options = array_merge($this->options, $options);

        // File: ext/standard/head.c, Line: 92
        if (empty($key) || preg_match("/[=,; \t\r\n\013\014]/", $key)) {
            throw new \InvalidArgumentException(sprintf('Invalid name \'%s\'.', $key));
        }

        // convert expiration time to a Unix timestamp
        if ($options['expire'] instanceof \DateTimeInterface) {
            $options['expire'] = $options['expire']->format('U');
        } 
        elseif (!is_numeric($options['expire'])) {
            $options['expire'] = strtotime($options['expire']);

            if (false === $options['expire'] || -1 === $options['expire']) {
                throw new \InvalidArgumentException('The expiration time is not valid.');
            }
        }

        setcookie ($key, $value,
            $options['expire'],
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly']
        );

        return $this;
    }


    /**
     * Not implemented
     * 
     * @throws \Exeption 
     *    When called
     */
    
    public function push($key, $value)
    {
        throw \Exception('Not implemented.');
    }


    /**
     * Deletes the cookie with the given key/name.
     *
     * @param  string  $key
     *    The key/name of the cookie
     *
     * @return self
     */
    
    public function delete($key)
    {
        if ($this->has($key)) {
            setcookie($key, null, time() - 3600);
        }

        return $this;
    }


    /**
     * Concats all cookie key/name - value pairs to one string.
     * May be used for debug proposes.
     *
     * @return string
     *    The string of cookie data.
     */
    
    public function __toString()
    {
        $string = '';

        foreach($_COOKIE as $key => $parameter) {
            $string .= sprintf("%s = %s\r\n", $key, $parameter);
        }

        return $string;
    }


    /**
     * Gets the parameter count of this collection to satisfy the Countable interface.
     *
     * @return int
     */
    
    public function count()
    {
        return count($_COOKIE);
    }


    /**
     * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
     *
     * @return ArrayIterator
     */
    
    public function getIterator()
    {
        return new ArrayIterator($_COOKIE);
    }
}