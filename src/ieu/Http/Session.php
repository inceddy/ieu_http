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

class Session implements SessionInterface, Countable, IteratorAggregate
{
    private $options = [
        'secure'     => false,
        'httponly'   => false,
        'cookieonly' => true
        /*
        'lifetime'   => 1800,
        'domain'     => '.example.com',
        'path'       => '/'
        */
    ];

    private $started;

    private static function started()
    {
        return function_exists('session_status') ? session_status() === \PHP_SESSION_ACTIVE : '' !== session_id();
    }

    private static function stoped()
    {
        return function_exists('session_status') ? session_status() === \PHP_SESSION_NONE : '' === session_id();
    }

    function __construct(Array $options = [])
    {
        $this->options = array_merge(session_get_cookie_params(), $this->options, $options);
        $this->started = $this->started();
    }

    public function start()
    {
        if ($this->started) {
            return $this;
        }

        if (self::started()) {
            throw new \Exception('Session is already running.');
        }

        
        if (ini_set('session.use_only_cookies', $this->options['cookieonly']) === false) {
            throw new \Exception('Error setting \'session.use_only_cookies\'.');
        }
       
        if (ini_set('session.cookie_httponly', $this->options['httponly']) === false) {
            throw new \Exception('Error setting \'session.cookie_httponly\'.');
        }

        session_set_cookie_params(
            $this->options['lifetime'],
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'], 
            $this->options['httponly']
        );

        if (!($this->started = session_start())) {
            throw new \Exception('Unable to start session.');
        }

        return $this;
    }

    public function stop()
    {
        if (!session_write_close()) {
            throw new \Exception('Unable to stop session.');
        }

        $this->started = false;

        return $this;
    }

    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public function get($key) 
    {
        return $this->has($key) ? $_SESSION[$key] : null;
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
        return $this;
    }

    public function push($key, $value)
    {
        // Not yet set
        if (!$this->has($key)) {
            return $this->set($key, [$value]);
        }

        // Already set with array value
        if (is_array($_SESSION[$key])) {
            $_SESSION[$key][] = $value;
            return $this;
        }

        // Set with single value
        $_SESSION[$key] = [$_SESSION[$key], $value];

        return $this;
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function destroy()
    {
        unset($_SESSION);
        $this->stop();
    }

    public function __toString()
    {
        $string = '';

        foreach($_SESSION as $key => $parameter) {
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
        return count($_SESSION);
    }


    /**
     * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
     *
     * @return ArrayIterator
     * 
     */
    
    public function getIterator()
    {
        return new ArrayIterator($_SESSION);
    }
}