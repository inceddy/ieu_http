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

class JsonResponse extends Response 
{

    /**
     * Constructor
     * Invokes a new HTTP response
     *
     * @param string  $content  The content to encode as JSON
     * @param integer $code     The response code (as listed above)
     * @param array   $headers  The header name-value-pairs to set
     *
     * @return self
     * 
     */
    
	public function __construct($content = null, $code = self::HTTP_OK, array $headers = [])
	{
        parent::__construct($content, $code, $headers);

        // Set JSON headers
        $this->setHeaders([
            'Content-type' => ['application/json', 'charset=utf-8']
        ]);
	}

    /**
     * Trys to JSON encode the given content.
     *
     * @param mixed $content  The content to encode
     */
    
    public function setContent($content)
    {
        if (!$json = json_encode($content)) {
            $this->setResponseCode(self::HTTP_INTERNAL_SERVER_ERROR);
            $json = json_encode(['status' => 'error', 'error_message' => json_last_error_msg()]);
        }

        $this->content = $json;

        return $this;
    }
}