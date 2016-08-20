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
     * Invokes a new HTTP response
     *
     * @param string  $content  The response body
     * @param integer $code     The response code (as listed above)
     * @param array   $headers  The header name-value-pairs to set
     *
     * @return self
     * 
     */
    
	public function __construct($target, $code = self::HTTP_FOUND, array $headers = [])
	{
        parent::__construct('', $code, $headers);
		$this->headers = new ParameterCollection();

		$this->setTarget($target);
	}

    public function setTarget($url)
    {
        if (!is_string($url) || $url == '') {
            throw new \InvalidArgumentException('No valid URL given.');
        }

        $this->target = $target;

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
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8')));
    }

    public function getTarget()
    {
        return $this->target;
    }
}