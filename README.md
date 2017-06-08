# HTTP framework components

## Request
Object representing a HTTP request.

## Response
Object representing a HTTP response.

## JsonResponse
Response transporting JSON encoded content.

## RedirectResponse
Response using redirect header and meta-refresh.

## Router
Routes request to the responsible handler.

## Route
URL path description, including variables, variable validation and wildcardpattern.

### Usage
```php
// {id} matches didgits of all length
$route1 = (new Route('/path/to/user/{id}'))
	->validate('id', '\d+');

// or shorthand

$route1 = (new Route('/path/to/user/{id|\d+}'));

// {everything} matches all allowed chars.
$route2 = new Route('/path/to/{everything}/update');

// Will match every route beginning with /path/to/
$route3 = new Route('/path/to/*')
```

## RouterProvider
For the use in `ieu\Container\Container` dependency containers. Implements the same interface as `Router`.
Handlers used in `RouterProvider::then`-method have access to all dependencies known to the container and
additional to `Request` (the current request) and `RouteParameter` (all variables found in the route pattern). 
### Usage
```php

// ieu\Container
(new ieu\Container\Container)
	->provider('Router', new ieu\Http\RouterProvier)
	->config(['RouterProvider', function($routerProvider){
		$routerProvider
			->get('/home', ['Request', 'RouteParameter', function($request, $parameter){
				return new Response('This is the homepage ');
			})
			->otherwise(['Request', 'Error', function($request, $error) {
				// handle error
			});
	}]);

// ieu\App
(new ieu\App)
	->config(['RouterProvider', function($routerProvider){
		$routerProvider
			->get('/home', ['Request', 'RouteParameter', function($request, $parameter){
				return new Response('This is the homepage ');
			});
	}]);
```
