# bitexpert/adrenaline

A PSR-7 micro framework built on top of the Adroit middleware to speed up your development ;)

[![Build Status](https://travis-ci.org/bitExpert/adrenaline.svg?branch=master)](https://travis-ci.org/bitExpert/adrenaline)
[![Dependency Status](https://www.versioneye.com/user/projects/5736e4e8a0ca35004cf77eb2/badge.svg?style=flat)](https://www.versioneye.com/user/projects/5736e4e8a0ca35004cf77eb2)

- [Getting started](#gettingstarted)
- [How to configure action resolvers](#howtoactionresolvers)
- [How to configure responder resolvers](#howtoresponderresolvers)
- [How to configure routing](#howtorouting)
- [How to implement an action](#howtoimplementanaction)
- [How to implement a responder](#howtoimplementaresponder)
- [How to use the middleware hooks](#howtomiddlewarehooks)
- [How to use an error handler](#howtoerrorhandler)
- [How to integrate with a DI container](#howtodi)
- [License](#license)

## <a name="gettingstarted"></a>Getting started
The preferred way of installing `bitexpert/adrenaline` is through Composer. Simply add `bitexpert/adrenaline` as a dependency:
```
composer.phar require bitexpert/adrenaline
```
### Prototyping
If you want to use Adrenaline for fast prototyping you proceed as follows:

* Create an index.php in your application's root directory.
* Add the following lines to it:

```php
<?php
use bitExpert\Adrenaline\Adrenaline;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$adrenaline = new Adrenaline();

$adrenaline->get('home', '/', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->rewind();
    $response->getBody()->write('Home');

    return $response;
});

$request = ServerRequestFactory::fromGlobals();
$response = new Response();
$adrenaline($request, $response);
```

* Start a server by using php -S localhost:8082 inside the same directoy.
* Browse to http://localhost:8082 and you should see "Home" on the screen.

So that's the basic setup. You may add more actions by using the implicit routing functions of
Adrenaline and of course you may use middleware hooks, error handler and custom resolvers just
as you would do in a productive application. This is just a possibility to give you a quick result for prototyping.

## <a name="howtoactionresolvers"></a>How to configure action resolvers
You may configure custom action resolvers for Adrenaline. By default `bitexpert/adroit`'s [CallableActionResolver](https://github.com/bitExpert/adroit/blob/master/src/bitExpert/Adroit/Action/Resolver/CallableActionResolver.php)
is added if no action resolvers are provided with the constructor which allows you to use simple Closures as actions (e.g. for protoyping case)

Be aware that you have to add this resolver explicitly if you define your own set of action resolvers if you still want to use it.

```php
<?php
$customActionResolver = new MyCustomActionResolver();
$adrenaline = new Adrenaline([$customActionResolver]);
```

## <a name="howtoresponderresolvers"></a>How to configure responder resolvers
You may configure custom action resolvers for Adrenaline. By default `bitexpert/adroit`'s [CallableResponderResolver](https://github.com/bitExpert/adroit/blob/master/src/bitExpert/Adroit/Responder/Resolver/CallableResponderResolver.php)
is added if no responder resolvers are provided with the constructor which allows you to use simple Closures as responders (e.g. for protoyping case)

Be aware that you have to add this resolver explicitly if you define your own set of responder resolvers if you still want to use it.

```php
<?php
$customResponderResolver = new MyCustomResponderResolver();
$adrenaline = new Adrenaline([], [$customResponderResolver]);
```

## <a name="howtorouting"></a>How to configure routing
With Adrenaline you may also use custom routers. Your custom router needs to inherit `bitexpert/pathfinder`'s
[Router](https://github.com/bitExpert/pathfinder/blob/master/src/bitExpert/Pathfinder/Router.php) interface.

```php
<?php
$customRouter = new MyCustomRouter();
$adrenaline = new Adrenaline([], [], $customRouter);
```

If you want to use a custom route class within the implicit route creation functions (get, post, etc.)
you can set the custom route route class:

```php
<?php
$adrenaline = new Adrenaline();
$adrenaline->setDefaultRouteClass(MyRoute::class);
```

For standard route definition, please have a look at the [pathfinder docs](https://github.com/bitExpert/pathfinder).

## <a name="howtoimplementanaction"></a>How to implement an action
See `bitexpert/adroit` [docs](https://github.com/bitExpert/adroit).

## <a name="howtoimplementaresponder"></a>How to implement a responder
See `bitexpert/adroit` [docs](https://github.com/bitExpert/adroit).

## <a name="howtomiddlewarehooks"></a>How to use the middleware hooks
Adrenaline offers the possibility to integrate your middlewares into the default processing of a request
by using the provided middleware hooks:

```php
<?php
$myMiddleware = function (ServerRequestInterface $request, ResponseInterface $response, callable $next = null) {
};

$adrenaline->beforeRouting($myMiddleware) //Middleware piped before routing middleware
$adrenaline->beforeResolveAction($myMiddleware) //Middleware piped before action resolver middleware
$adrenaline->beforeExecuteAction($myMiddleware) //Middleware piped before action executor middleware
$adrenaline->beforeResolveResponder($myMiddleware) //Middleware piped before responder resolver middleware
$adrenaline->beforeExecuteResponder($myMiddleware) //Middleware piped before responder executor middleware
$adrenaline->beforeEmit($myMiddleware) //Middleware piped before emitter
```

These hooks are chainable and you may call them multiple times. Each call will push the provided
middleware to the according stack. As middlewares you may either use a simple closures or use your own dedicated
classes implementing the __invoke method with the according signature.

## <a name="howtoerrorhandler"></a>How to use an error handler
You also may define an error handler which will be used for uncaught errors occuring while Adrenaline
processes the request. You may either use a simple closure or implement your own dedicated class for error handling.
**UPDATE**
Since update to Stratigility 1.3 (we use MiddelwarePipe internally) we had to change the errorhandling due to deprecation warnings. Nevertheless we made the old configuration style working due to backwards compatibility:
```php
<?php

// simple closure
$adrenaline->setErrorHandler(function (ServerRequestInterface $request, ResponseInterface $response, $err) {
    return $response->withStatus(500);
});

// class which implements __invoke with same signature as above
$adrenaline->setErrorHandler(new MyCustomErrorHandlerClass());
```
We recommend to use the new errorhandling since using the "old" error handler may become deprecated in adrenaline also after a while:
```php
$adrenaline->setErrorHandler(new ErrorHandler(new Request(), function ($err, ServerRequestInterface $request, ResponseInterface $response) {
    return $response->withStatus(500);
});

// class which implements __invoke with same signature as above
$adrenaline->setErrorHandler(new ErrorHandler(new Request(), new MyErrorResponseGenerator());
```
For further information please have a look at: https://docs.zendframework.com/zend-stratigility/migration/to-v2/#error-handling

## <a name="howtodi"></a>How to integrate with a DI container
If you want to use a DI container, you may use the according resolvers to make use of it:

```php
<?php
/** @var \Interop\Container\ContainerInterface $container */
$actionResolver = new \bitExpert\Adroit\Action\Resolver\ContainerActionResolver($container);
/** @var \Interop\Container\ContainerInterface $container */
$responderResolver = new \bitExpert\Adroit\Responder\Resolver\ContainerAwareResponderResolver($container);

// Adrenaline will use your containers for resolving actions and responders
$adrenaline = new Adrenaline([$actionResolver], [$responderResolver]);
```

For further instructions you may also have a look at the `bitexpert/adroit` [docs](https://github.com/bitExpert/adroit).


##<a name="licence"></a>License

Adrenaline is released under the Apache 2.0 license.
