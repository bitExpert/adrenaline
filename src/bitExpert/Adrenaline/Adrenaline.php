<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline;

use bitExpert\Adrenaline\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;
use bitExpert\Adroit\Action\Resolver\CallableActionResolver;
use bitExpert\Adroit\AdroitMiddleware;
use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Domain\Payload;
use bitExpert\Adroit\Responder\Resolver\CallableResponderResolver;
use bitExpert\Adroit\Responder\ResponderMiddleware;
use bitExpert\Pathfinder\Middleware\BasicRoutingMiddleware;
use bitExpert\Pathfinder\Middleware\RoutingMiddleware;
use bitExpert\Pathfinder\Psr7Router;
use bitExpert\Pathfinder\Route;
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
     * @var RoutingMiddleware
     */
    protected $routingMiddleware;
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
     * Adrenaline constructor.
     *
     * Very flexible constructor to customize Adrenaline how you want it to be.
     * If you don't need any customizations concerning routing / action / responder middlewares
     * please use {@link \bitExpert\Adrenaline\Adrenaline::create} instead
     *
     * @param RoutingMiddleware $routingMiddleware
     * @param ActionMiddleware $actionMiddleware
     * @param ResponderMiddleware $responderMiddleware
     * @param EmitterInterface|null $emitter
     */
    public function __construct(
        RoutingMiddleware $routingMiddleware,
        ActionMiddleware $actionMiddleware,
        ResponderMiddleware $responderMiddleware,
        EmitterInterface $emitter = null
    ) {
        $this->routingMiddleware = $routingMiddleware;
        $this->defaultRouteClass = Route::class;
        $this->beforeRoutingMiddlewares = [];
        $this->beforeEmitterMiddlewares = [];

        $this->emitter = $emitter ?: new SapiEmitter();

        $this->errorHandler = null;

        parent::__construct($actionMiddleware, $responderMiddleware);
    }

    /**
     * @inheritdoc
     */
    protected function initialize()
    {
        $this->pipeEach($this->beforeRoutingMiddlewares);
        $this->pipe($this->routingMiddleware);

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
     * Returns the used router
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->routingMiddleware->getRouter();
    }

    /**
     * Sets the default route class to use for implicit
     * route creation
     *
     * @param $defaultRouteClass
     * @throws \InvalidArgumentException
     */
    public function setDefaultRouteClass($defaultRouteClass)
    {
        if ($defaultRouteClass === Route::class) {
            $this->defaultRouteClass = $defaultRouteClass;
        } else {
            $routeClass = $defaultRouteClass;
            while ($parent = get_parent_class($routeClass)) {
                if ($parent === Route::class) {
                    $this->defaultRouteClass = $defaultRouteClass;
                    break;
                } else {
                    $routeClass = $parent;
                }
            }

            if ($this->defaultRouteClass !== $defaultRouteClass) {
                throw new \InvalidArgumentException(sprintf(
                    'You tried to set "%s" as default route class which does not inherit "%s"',
                    $defaultRouteClass,
                    Route::class
                ));
            }
        }
    }

    /**
     * Creates a route using given params
     *
     * @param mixed $methods
     * @param string $name
     * @param string $path
     * @param mixed $target
     * @param \bitExpert\Pathfinder\Matcher\Matcher[] $matchers
     * @return Route
     */
    protected function createRoute($methods, $name, $path, $target, array $matchers = [])
    {
        /** @var Route $route */
        $route = forward_static_call([$this->defaultRouteClass, 'create'], $methods, $path, $target);
        $route = $route->named($name);

        foreach ($matchers as $param => $paramMatchers) {
            $route = $route->ifMatches($param, $paramMatchers);
        }

        return $route;
    }

    /**
     * Adds a GET route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return Adrenaline
     */
    public function get($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('GET', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a POST route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return Adrenaline
     */
    public function post($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('POST', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a PUT route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return Adrenaline
     */
    public function put($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('PUT', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a DELETE route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return Adrenaline
     */
    public function delete($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('DELETE', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds an OPTIONS route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return Adrenaline
     */
    public function options($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('OPTIONS', $name, $path, $target, $matchers);
        $this->addRoute($route);
        return $this;
    }

    /**
     * Adds a PATCH route
     *
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @return Adrenaline
     */
    public function patch($name, $path, $target, array $matchers = [])
    {
        $route = $this->createRoute('PATCH', $name, $path, $target, $matchers);
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
        $this->getRouter()->addRoute($route);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function createDefault($routingResultAttribute, array $actionResolvers, array $responderResolvers)
    {
        $router = new Psr7Router();

        $routingMiddleware = new BasicRoutingMiddleware(
            $router,
            $routingResultAttribute
        );

        $actionMiddleware = new ActionResolverMiddleware(
            $actionResolvers,
            $routingResultAttribute,
            Payload::class,
            true
        );

        $responderMiddleware = new ResponderResolverMiddleware(
            $responderResolvers,
            Payload::class,
            true
        );

        return new self($routingMiddleware, $actionMiddleware, $responderMiddleware);
    }

    /**
     * Creates a new {@link \bitExpert\Adrenaline\Adrenaline} using the default middlewares
     * for routing, action resolving and responder resolving
     *
     * This factory method should be used, when creating a productive application with sensible defaults,
     * if you don't need any customizations concerning the basic middlewares (Routing, Action, Responder)
     *
     * @param $actionResolvers
     * @param $responderResolvers
     * @param EmitterInterface|null $emitter
     * @param Router $router
     * @return Adrenaline
     */
    public static function strict(
        $actionResolvers,
        $responderResolvers,
        Router $router = null,
        EmitterInterface $emitter = null
    ) {
        $router = $router ?: new Psr7Router();

        $routingMiddleware = new BasicRoutingMiddleware(
            $router,
            RoutingResult::class
        );

        $actionMiddleware = new ActionResolverMiddleware(
            $actionResolvers,
            RoutingResult::class,
            Payload::class,
            true
        );

        $responderMiddleware = new ResponderResolverMiddleware(
            $responderResolvers,
            Payload::class,
            true
        );

        return new self($routingMiddleware, $actionMiddleware, $responderMiddleware, $emitter);
    }

    /**
     * Creates a new {@link \bitExpert\Adrenaline\Adrenaline} using the default middlewares
     * for routing, action resolving and responder resolving
     *
     * This factory method should be used, when creating a productive application with sensible defaults,
     * if you don't need any customizations concerning the basic middlewares (Routing, Action, Responder)
     *
     * @param $actionResolvers
     * @param $responderResolvers
     * @param EmitterInterface|null $emitter
     * @param Router $router
     * @return Adrenaline
     */
    public static function lenient(
        $actionResolvers,
        $responderResolvers,
        Router $router = null,
        EmitterInterface $emitter = null
    ) {
        $router = $router ?: new Psr7Router();

        $routingMiddleware = new BasicRoutingMiddleware(
            $router,
            RoutingResult::class
        );

        $actionMiddleware = new ActionResolverMiddleware(
            $actionResolvers,
            RoutingResult::class,
            Payload::class
        );

        $responderMiddleware = new ResponderResolverMiddleware($responderResolvers, Payload::class);

        return new self($routingMiddleware, $actionMiddleware, $responderMiddleware, $emitter);
    }

    /**
     * Creates a new {@link \bitExpert\Adrenaline\Adrenaline} using {@link \bitExpert\Pathfinder\Psr7Router} as default
     * router, and {@link \bitExpert\Adroit\Resolver\CallableResolver}s as action resolver and responder resolver
     *
     * This factory method may be used for rapid development of prototypes and is not recommended for production usage,
     * since your code will look not that pretty and will likely not be well structured
     *
     * @return Adrenaline
     */
    public static function prototyping()
    {
        $actionResolvers = [new CallableActionResolver()];
        $responderResolvers = [new CallableResponderResolver()];

        return self::lenient($actionResolvers, $responderResolvers);
    }
}
