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

    public function __construct(Array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function has($key)
    {
        return isset($_COOKIE[$key]);
    }

    public function get($key) 
    {
        return $this->has($key) ? $_COOKIE[$key] : null;
    }

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

    public function push($key, $value)
    {
        throw \Exception('Not implemented.');
    }

    public function delete($key)
    {
        if ($this->has($key)) {
            setcookie($key, null, time() - 3600);
        }

        return $this;
    }

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
     * 
     */
    
    public function count()
    {
        return count($_COOKIE);
    }


    /**
     * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
     *
     * @return ArrayIterator
     * 
     */
    
    public function getIterator()
    {
        return new ArrayIterator($_COOKIE);
    }
}