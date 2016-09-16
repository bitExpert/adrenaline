<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types = 1);

namespace bitExpert\Adrenaline;

use bitExpert\Adroit\Action\Resolver\CallableActionResolver;
use bitExpert\Adroit\AdroitMiddleware;
use bitExpert\Adrenaline\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adroit\Responder\Resolver\CallableResponderResolver;
use bitExpert\Pathfinder\Middleware\BasicRoutingMiddleware;
use bitExpert\Pathfinder\Psr7Router;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\RouteBuilder;
use bitExpert\Pathfinder\Router;
use bitExpert\Pathfinder\RoutingResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;

/**
 * Class Adrenaline
 *
 * Main application class of the framework
 */
class Adrenaline extends AdroitMiddleware
{
    /**
     * @var callable[]
     */
    protected $beforeRoutingMiddlewares;
    /**
     * @var callable[]
     */
    protected $beforeEmitterMiddlewares;
    /**
     * @var string
     */
    protected $defaultRouteClass;
    /**
     * @var callable
     */
    protected $errorHandler;
    /**
     * @var EmitterInterface
     */
    protected $emitter;
    /**
     * @var Router
     */
    protected $router;

    /**
     * Adrenaline constructor.
     *
     * Very flexible constructor to customize Adrenaline how you want it to be.
     * If you don't need any customizations concerning routing / action / responder middlewares
     * please use {@link \bitExpert\Adrenaline\Adrenaline::create} instead
     *
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $actionResolvers
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $responderResolvers
     * @param Router|null $router
     * @param EmitterInterface|null $emitter
     */
    public function __construct(
        array $actionResolvers = [],
        array $responderResolvers = [],
        Router $router = null,
        EmitterInterface $emitter = null
    ) {
        $actionResolvers = count($actionResolvers) ? $actionResolvers : [new CallableActionResolver()];
        $responderResolvers = count($responderResolvers) ? $responderResolvers : [new CallableResponderResolver()];

        parent::__construct(RoutingResult::class, $actionResolvers, $responderResolvers);

        $this->router = $router ?: new Psr7Router();

        $this->defaultRouteClass = Route::class;
        $this->beforeRoutingMiddlewares = [];
        $this->beforeEmitterMiddlewares = [];

        $this->emitter = $emitter ?: new SapiEmitter();

        $this->errorHandler = null;
    }

    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        $routingMiddleware = $this->getRoutingMiddleware($this->router, RoutingResult::class);

        $this->pipeEach($this->beforeRoutingMiddlewares);
        $this->pipe($routingMiddleware);

        parent::initialize();

        $this->pipeEach($this->beforeEmitterMiddlewares);
    }

    /**
     * Wraps the given error handler for MiddlewarePipe usage
     * The {@link FinalHandler
     * Because of this wrapper it will only be called when an error occurs
     *
     * @param callable $errorHandler
     * @param callable|null $out
     * @return \Closure
     */
    protected function encapsulateErrorHandler(callable $errorHandler, callable $out = null)
    {
        return function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            $err = null
        ) use (
            $errorHandler,
            $out
        ) {

            if (!$err) {
                if ($out) {
                    $response = $out($request, $response);
                }
                return $response;
            }

            return $errorHandler($request, $response, $err);
        };
    }

    /**
     * Adds a middleware to the stack which will be executed prior the routing middleware
     *
     * @param callable $middleware
     * @return Adrenaline
     */
    public function beforeRouting(callable $middleware)
    {
        $this->beforeRoutingMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Adds a middleware to the stack which will be executed prior the emitter
     *
     * @param callable $middleware
     * @return Adrenaline
     */
    public function beforeEmitter(callable $middleware)
    {
        $this->beforeEmitterMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Sets an errorHandler which will be executed when an exception is thrown during
     * execution of {@link \bitExpert\Adrenaline\Adrenaline}
     *
     * @param callable $errorHandler
     */
    public function setErrorHandler(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        if ($this->errorHandler) {
            $out = $this->encapsulateErrorHandler($this->errorHandler, $out);
        }

        $response = parent::__invoke($request, $response, $out);
        $this->emitter->emit($response);
    }

    /**
     * Returns the routing middleware to use
     *
     * @param Router $router
     * @param string $routingResultAttribute
     * @return \bitExpert\Pathfinder\Middleware\RoutingMiddleware
     */
    protected function getRoutingMiddleware(Router $router, $routingResultAttribute)
    {
        return new BasicRoutingMiddleware($router, $routingResultAttribute);
    }

    /**
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $actionResolvers
     * @param string $routingResultAttribute
     * @param string $actionAttribute
     * @return ActionResolverMiddleware
     */
    protected function getActionResolverMiddleware($actionResolvers, $routingResultAttribute, $actionAttribute)
    {
        return new ActionResolverMiddleware($actionResolvers, $routingResultAttribute, $actionAttribute);
    }

    /**
     * Sets the default route class to use for implicit
     * route creation
     *
     * @param $defaultRouteClass
     */
    public function setDefaultRouteClass($defaultRouteClass)
    {
        $this->defaultRouteClass = $defaultRouteClass;
    }

    /**
     * Creates a route using given params
     *
     * @param array $methods
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Route
     */
    protected function createRoute(array $methods, $name, $path, $target, array $matchers = [])
    {
        $builder = RouteBuilder::route($this->defaultRouteClass);

        $builder->from($path)->to($target);

        foreach ($methods as $method) {
            $builder->accepting($method);
        }

        $builder->named($name);

        foreach ($matchers as $param => $paramMatchers) {
            foreach ($paramMatchers as $matcher) {
                $builder->ifMatches($param, $matcher);
            }
        }

        return $builder->build();
    }

    /**
     * Adds a GET route
     *
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Adrenaline
     */
    public function get($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute(['GET'], $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a POST route
     *
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Adrenaline
     */
    public function post($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute(['POST'], $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a PUT route
     *
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Adrenaline
     */
    public function put($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute(['PUT'], $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a DELETE route
     *
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Adrenaline
     */
    public function delete($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute(['DELETE'], $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds an OPTIONS route
     *
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Adrenaline
     */
    public function options($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute(['OPTIONS'], $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a PATCH route
     *
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param callable[][] $matchers
     * @return Adrenaline
     */
    public function patch($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute(['PATCH'], $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds given route to router
     *
     * @param Route $route
     * @throws \InvalidArgumentException
     * @return Adrenaline
     */
    public function addRoute(Route $route)
    {
        $this->router->addRoute($route);
        return $this;
    }
}
