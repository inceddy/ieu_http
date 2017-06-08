<?php

use ieu\Http\Request;
use ieu\Http\Url;


class RequestMock extends Request {

	private $method = Request::HTTP_ALL;

	private $url;

	public function setMethod(int $method)
	{
		$this->method = $method;
	}

	public function isMethod($method)
	{
		return (bool) $method & $this->method;
	}

	public function setUrl($url)
	{
		$this->url = Url::from($url);
		return $this;
	}

	public function getUrl()
	{
		return $this->url;
	}
}