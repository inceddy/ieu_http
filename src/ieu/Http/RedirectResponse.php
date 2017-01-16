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

class RedirectResponse extends Response
{
    protected $target;


    /**
     * Constructor
     * Invokes a new HTTP redirect response
     *
     * @param string  $target
     *    The target URL
     * @param integer $code     
     *    The response code (as listed above)
     * @param array   $headers  
     *    The header name-value-pairs to set
     *
     * @return self
     * 
     */
    
	public function __construct($target, $code = self::HTTP_FOUND, array $headers = [])
	{
        parent::__construct('', $code, $headers);
		$this->setTarget($target);
	}


    /**
     * Sets the target URL where the redirections points to.
     *
     * @param string $target
     *    The target URL
     *
     * @return self
     * 
     */
    
    public function setTarget($target)
    {
        if (!is_string($target) || $target == '') {
            throw new \InvalidArgumentException('No valid URL given.');
        }

        $this->target = $target;

        // Set meta refresh content if header location is ignored
        $this->setContent(
            sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0; url=%1$s" />
    </head>
    <body>
        <p>Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($target, ENT_QUOTES, 'UTF-8')));

        // Set header location
        $this->setHeader('Location', $target);

        return $this;
    }

    public function getTarget()
    {
        return $this->target;
    }
}