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

use bitExpert\Adrenaline\Action\Resolver\ActionResolverMiddleware;
use bitExpert\Adrenaline\Helper\DeeplyInheritedRoute;
use bitExpert\Adrenaline\Helper\InheritedRoute;
use bitExpert\Adrenaline\Helper\TestMiddleware;
use bitExpert\Adroit\Action\Executor\ActionExecutorMiddleware;
use bitExpert\Adroit\Action\Resolver\ActionResolver;
use bitExpert\Adroit\Responder\Executor\ResponderExecutorMiddleware;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;
use bitExpert\Pathfinder\Matcher\NumericMatcher;
use bitExpert\Pathfinder\Middleware\BasicRoutingMiddleware;
use bitExpert\Pathfinder\Middleware\RoutingMiddleware;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\RouteBuilder;
use bitExpert\Pathfinder\Router;
use bitExpert\Pathfinder\RoutingResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Adrenaline}.
 */
class AdrenalineUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var EmitterInterface
     */
    protected $emitter;
    /**
     * @var Adrenaline
     */
    protected $application;
    /**
     * @var RoutingMiddleware
     */
    protected $routingMiddleware;
    /**
     * @var ActionResolverMiddleware
     */
    protected $actionResolverMiddleware;
    /**
     * @var ActionExecutorMiddleware
     */
    protected $actionExecutorMiddleware;
    /**
     * @var ResponderResolverMiddleware
     */
    protected $responderResolverMiddleware;
    /**
     * @var ResponderExecutorMiddleware
     */
    protected $responderExecutorMiddleware;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest([], [], '/', 'GET');
        $this->response = new Response();
        $this->emitter = $this->getMock(EmitterInterface::class);

        $this->routingMiddleware = $this->getMockBuilder(BasicRoutingMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $this->actionResolverMiddleware = $this->getMockBuilder(ActionResolverMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $this->actionExecutorMiddleware = $this->getMockBuilder(ActionExecutorMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();
        $this->responderResolverMiddleware = $this->getMockBuilder(ResponderResolverMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $this->responderExecutorMiddleware = $this->getMockBuilder(ResponderExecutorMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

    }

    protected function getMockedAdrenaline()
    {
        $middleware = $this->getMockBuilder(Adrenaline::class)
            ->setMethods([
                'pipe'
            ])
            ->setConstructorArgs([
                [],
                [],
                null,
                $this->emitter
            ])
            ->getMock();

        return $middleware;
    }

    /**
     * @test
     */
    public function setErrorHandlerWillBeCalledWhenExceptionIsThrown()
    {
        $app = new Adrenaline([], [], null, $this->emitter);
        $called = false;
        $errorHandler = function (ServerRequestInterface $request, ResponseInterface $response, $err) use (&$called) {
            $called = true;
            return $response;
        };
        $app->setErrorHandler($errorHandler);
        $app->addRoute(
            RouteBuilder::route()
                ->get('/')
                ->to(function (ServerRequestInterface $request, ResponseInterface $response) {
                    throw new \Exception();
                })
                ->named('home')
                ->build()
        );
        $app($this->request, $this->response);
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function beforeRoutingMiddlewareWillBePipedBeforeRoutingMiddleware()
    {
        $expectedOrder = [
            TestMiddleware::class,
            BasicRoutingMiddleware::class,
            ActionResolverMiddleware::class,
            ActionExecutorMiddleware::class,
            ResponderResolverMiddleware::class,
            ResponderExecutorMiddleware::class
        ];

        $order = [];

        $app = $this->getMockedAdrenaline();
        $app->expects($this->any())
            ->method('pipe')
            ->will($this->returnCallback(function ($middleware) use (&$order) {
                $order[] = get_class($middleware);
            }));

        $route = RouteBuilder::route()
            ->get('/')
            ->to('home')
            ->build();

        $routingResult = RoutingResult::forSuccess($route);
        $app->beforeRouting(new TestMiddleware());
        $this->request = $this->request->withAttribute(RoutingResult::class, $routingResult);
        $app->__invoke($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }


    /**
     * @test
     */
    public function beforeEmitterMiddlewareWillBePipedBeforeEmitter()
    {
        $expectedOrder = [
            BasicRoutingMiddleware::class,
            ActionResolverMiddleware::class,
            ActionExecutorMiddleware::class,
            ResponderResolverMiddleware::class,
            ResponderExecutorMiddleware::class,
            TestMiddleware::class
        ];

        $order = [];

        $app = $this->getMockedAdrenaline();
        $app->expects($this->any())
            ->method('pipe')
            ->will($this->returnCallback(function ($middleware) use (&$order) {
                $order[] = get_class($middleware);
            }));

        $route = RouteBuilder::route()
            ->get('/')
            ->to('home')
            ->build();

        $routingResult = RoutingResult::forSuccess($route);
        $app->beforeEmitter(new TestMiddleware());
        $this->request = $this->request->withAttribute(RoutingResult::class, $routingResult);
        $app->__invoke($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function getProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('get');
    }

    /**
     * @test
     */
    public function postProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('post');
    }

    /**
     * @test
     */
    public function putProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('put');
    }

    /**
     * @test
     */
    public function deleteProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('delete');
    }

    /**
     * @test
     */
    public function optionsProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('options');
    }

    /**
     * @test
     */
    public function patchProducesAndRegistersARoute()
    {
        $this->routeCreationTestFunction('patch');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsExceptionIfDefaultRouteClassDoesNotInheritRoute()
    {
        $app = new Adrenaline();
        $app->setDefaultRouteClass(\stdClass::class);
        $app->get('home', '/', function () {

        });
    }

    /**
     * @test
     */
    public function acceptsRouteAsDefaultRouteClass()
    {
        $thrown = false;

        try {
            $app = new Adrenaline();
            $app->setDefaultRouteClass(Route::class);
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
        }

        $this->assertFalse($thrown);
    }

    /**
     * @test
     */
    public function acceptsInheritedRouteClassAsDefaultRouteClass()
    {
        $thrown = false;
        try {
            $app = new Adrenaline();
            $app->setDefaultRouteClass(InheritedRoute::class);
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
        }

        $this->assertFalse($thrown);
    }

    /**
     * @test
     */
    public function acceptsDeeplyInheritedRouteClassAsDefaultRouteClass()
    {
        $thrown = false;
        try {
            $app = new Adrenaline();
            $app->setDefaultRouteClass(DeeplyInheritedRoute::class);
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
        }

        $this->assertFalse($thrown);
    }

    /**
     * @test
     */
    public function usesModifiedDefaultRouteClass()
    {
        $name = 'user';
        $path = '/user/[:id]';
        $target = 'userAction';
        $matchers = [
            'id' => [$this->getMock(NumericMatcher::class)]
        ];

        $defaultRouteClass = DeeplyInheritedRoute::class;

        $router = $this->createRouteCreationTestRouter(
            strtoupper('GET'),
            $name,
            $path,
            $target,
            $matchers,
            $defaultRouteClass
        );

        $app = new Adrenaline([], [], $router, $this->emitter);
        $app->setDefaultRouteClass(DeeplyInheritedRoute::class);
        $app->get($name, $path, $target, $matchers);
    }

    /**
     * @test
     */
    public function outGetsCalledIfGivenAndNoErrorHandlerIsSet()
    {
        $called = false;

        $app = new Adrenaline([], [], null, $this->emitter);
        $app($this->request, $this->response, function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function outGetsCalledIfGivenAndErrorHandlerIsSetAndNoErrorOccurs()
    {
        $expected = [
            'outCalled'
        ];

        $called = [];
        $resolver = $this->getMock(ActionResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response;
            }));

        $app = new Adrenaline([$resolver], [], null, $this->emitter);
        $app->addRoute(
            RouteBuilder::route()
                ->get('/')
                ->to(function (ServerRequestInterface $request, ResponseInterface $response) {
                    return $response;
                })
                ->named('home')
                ->build()
        );

        $app->setErrorHandler(function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            $err
        ) use (
            &$called
        ) {
            $called[] = 'errCalled';
            return $response;
        });

        $app($this->request, $this->response, function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use (
            &$called
        ) {
            $called[] = 'outCalled';
            return $response;
        });
        
        $this->assertSame($expected, $called);
    }

    /**
     * @test
     */
    public function outDoesNotGetCalledIfGivenAndErrorHandlerIsSetAndErrorOccurs()
    {
        $expected = [
            'errCalled'
        ];

        $called = [];

        $resolver = $this->getMock(ActionResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue(function () {
                throw new \Exception();
            }));

        $app = new Adrenaline([$resolver], [], null, $this->emitter);

        $app->setErrorHandler(function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            $err
        ) use (
            &$called
        ) {
            $called[] = 'errCalled';
            return $response;
        });

        $app($this->request, $this->response, function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use (
            &$called
        ) {
            $called[] = 'outCalled';
            return $response;
        });

        $this->assertSame($expected, $called);
    }

    /**
     * Returns a middleware. You may define an additional callable which will be executed
     * in front of the default behavior for testing purpose (e.g. testing call order)
     *
     * @param callable|null $specializedFn
     * @return \Closure
     */
    protected function createTestMiddleware(callable $specializedFn = null)
    {
        return function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next = null
        ) use ($specializedFn) {
            if ($specializedFn) {
                $specializedFn();
            }

            if ($next) {
                $response = $next($request, $response);
            }

            return $response;
        };
    }

    /**
     * Creates a route creation testing function for the appropriate shorthand WebApplication function
     * to create a route
     *
     * @param string $method
     */
    protected function routeCreationTestFunction($method)
    {
        $name = 'user';
        $path = '/user/[:id]';
        $target = 'userAction';
        $matchers = [
            'id' => [$this->getMock(NumericMatcher::class)]
        ];

        $router = $this->createRouteCreationTestRouter(strtoupper($method), $name, $path, $target, $matchers);
        $app = new Adrenaline([], [], $router, $this->emitter);
        $function = strtolower($method);
        $app->$function($name, $path, $target, $matchers);
    }

    /**
     * Creates a router which observes the incoming route and compares the properties
     * to the properties having been used to create the route
     *
     * @param $method
     * @param $name
     * @param $path
     * @param $target
     * @param $matchers
     * @param $class
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRouteCreationTestRouter($method, $name, $path, $target, $matchers, $class = null)
    {
        $router = $this->getMockForAbstractClass(Router::class, ['addRoute']);
        $self = $this;
        $router->expects($this->once())
            ->method('addRoute')
            ->will($this->returnCallback(function (
                Route $route
            ) use (
                $self,
                $method,
                $name,
                $path,
                $target,
                $matchers,
                $class
            ) {
                if ($class !== null) {
                    $this->assertEquals(get_class($route), $class);
                }
                $routeMethods = $route->getMethods();
                $self->assertContains($method, $routeMethods);
                $routePath = $route->getPath();
                $self->assertEquals($routePath, $path);
                $routeName = $route->getName();
                $self->assertEquals($routeName, $name);
                $routeTarget = $route->getTarget();
                $self->assertEquals($routeTarget, $target);
                $routeMatchers = $route->getMatchers();
                $self->assertEquals($routeMatchers, $matchers);
            }));

        return $router;
    }
}
