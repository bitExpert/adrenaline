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
use bitExpert\Pathfinder\Middleware\RoutingMiddleware;
use bitExpert\Pathfinder\Psr7Router;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\RouteBuilder;
use bitExpert\Pathfinder\Router;
use bitExpert\Pathfinder\RoutingResult;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Stratigility\NoopFinalHandler;

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

        if ($this->errorHandler) {
            $this->pipe($this->errorHandler);
        }

        $this->pipeEach($this->beforeRoutingMiddlewares);
        $this->pipe($routingMiddleware);

        parent::initialize();

        $this->pipeEach($this->beforeEmitterMiddlewares);
    }

    /**
     * Adds a middleware to the stack which will be executed prior the routing middleware
     *
     * @param callable $middleware
     * @return Adrenaline
     */
    public function beforeRouting(callable $middleware) : self
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
    public function beforeEmitter(callable $middleware) : self
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
        $this->raiseThrowables();
        $errorHandler = $this->normalizeErrorHandler($errorHandler);
        $this->errorHandler = $errorHandler;
    }

    /**
     * Checks if given $errorHandler is an instance {@link Zend\Stratigility\Middleware\ErrorHandler}
     * If it is a "legacy" error handler, it will be converted due to backwards compatibility
     *
     * @param callable $errorHandler
     * @return callable|ErrorHandler
     */
    protected function normalizeErrorHandler(callable $errorHandler)
    {
        if ($errorHandler instanceof ErrorHandler) {
            return $errorHandler;
        }

        $legacyResponseGenerator = function (
            $e,
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use ($errorHandler) {
            return $errorHandler($request, $response, $e);
        };

        $errorHandler = new ErrorHandler(new Response(), $legacyResponseGenerator);
        return $errorHandler;
    }
    
    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        $out = $out ?: new NoopFinalHandler();
        $response = parent::__invoke($request, $response, $out);
        $this->emitter->emit($response);
    }

    /**
     * Returns the routing middleware to use
     *
     * @param Router $router
     * @param string $routingResultAttribute
     * @return RoutingMiddleware
     */
    protected function getRoutingMiddleware(Router $router, string $routingResultAttribute) : RoutingMiddleware
    {
        return new BasicRoutingMiddleware($router, $routingResultAttribute);
    }

    /**
     * @param \bitExpert\Adroit\Action\Resolver\ActionResolver[] $actionResolvers
     * @param string $routingResultAttribute
     * @param string $actionAttribute
     * @return ActionResolverMiddleware
     */
    protected function getActionResolverMiddleware(
        array $actionResolvers,
        string $routingResultAttribute,
        string $actionAttribute
    ) : \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware {
        return new ActionResolverMiddleware($actionResolvers, $routingResultAttribute, $actionAttribute);
    }

    /**
     * Sets the default route class to use for implicit route creation.
     *
     * @param string $defaultRouteClass
     */
    public function setDefaultRouteClass(string $defaultRouteClass)
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
     * @param array $matchers
     * @return Route
     */
    protected function createRoute(array $methods, string $name, string $path, $target, array $matchers = []) : Route
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
    public function get(string $name, string $path, $target, array $matchers = []) : self
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
    public function post(string $name, string $path, $target, array $matchers = []) : self
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
    public function put(string $name, string $path, $target, array $matchers = []) : self
    {
        $route = $this->createRoute([RequestMethodInterface::METHOD_PUT], $name, $path, $target, $matchers);
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
    public function delete(string $name, string $path, $target, array $matchers = []) : self
    {
        $route = $this->createRoute([RequestMethodInterface::METHOD_DELETE], $name, $path, $target, $matchers);
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
    public function options(string $name, string $path, $target, array $matchers = []) : self
    {
        $route = $this->createRoute([RequestMethodInterface::METHOD_OPTIONS], $name, $path, $target, $matchers);
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
    public function patch(string $name, string $path, $target, array $matchers = []) : self
    {
        $route = $this->createRoute([RequestMethodInterface::METHOD_PATCH], $name, $path, $target, $matchers);
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
    public function addRoute(Route $route) : self
    {
        $this->router->addRoute($route);
        return $this;
    }
}
