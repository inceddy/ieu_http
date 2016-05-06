<?php

use ieu\Http\Request;


class RequestMock extends Request {

	private static $method = Request::HTTP_GET;

	private $uri = '';

	public static function isMethod($method)
	{
		return true;
	}

	public function setUri($uri)
	{
		$this->uri = $uri;
		return $this;
	}

	public function getUri()
	{
		return $this->uri;
	}
}