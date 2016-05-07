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

use bitExpert\Adrenaline\Helper\DeeplyInheritedRoute;
use bitExpert\Adrenaline\Helper\InheritedRoute;
use bitExpert\Adroit\Action\Resolver\ActionResolver;
use bitExpert\Pathfinder\Matcher\NumericMatcher;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\EmitterInterface;
use bitExpert\Adroit\Action\ActionMiddleware;
use bitExpert\Adroit\Responder\ResponderMiddleware;
use bitExpert\Pathfinder\Middleware\RoutingMiddleware;

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
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest([], [], '/', 'GET');
        $this->response = new Response();
        $this->emitter = $this->getMock(EmitterInterface::class);
    }

    /**
     * @test
     */
    public function setErrorHandlerWillBeCalledWhenExceptionIsThrown()
    {
        $app = Adrenaline::lenient([], [], null, $this->emitter);
        $called = false;
        $errorHandler = function (ServerRequestInterface $request, ResponseInterface $response, $err) use (&$called){
            $called = true;
            return $response;
        };
        $app->setErrorHandler($errorHandler);
        $app->addRoute(
            Route::get('/')->to(function (ServerRequestInterface $request, ResponseInterface $response) {
                throw new \Exception();
            })->named('home')
        );
        $app($this->request, $this->response);
        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function beforeRoutingMiddlewareWillBeCalledBeforeRoutingMiddleware()
    {
        $expectedOrder = [
            'beforeRouting',
            'routing'
        ];

        $order = [];
        $beforeRoutingMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeRouting';
        });

        $routingMiddleware = $this->getMockBuilder(RoutingMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $routingMiddlewareStub = $this->createTestMiddleware(function () use(&$order) {
            $order[] = 'routing';
        });

        $routingMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($routingMiddlewareStub));

        $actionMiddleware = $this->getMockBuilder(ActionMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responderMiddleware = $this->getMockBuilder(ResponderMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $app = new Adrenaline($routingMiddleware, $actionMiddleware, $responderMiddleware, $this->emitter);

        $app->beforeRouting($beforeRoutingMiddleware);
        $app($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }

    /**
     * @test
     */
    public function beforeActionMiddlewareWillBeCalledBeforeActionMiddleware()
    {
        $expectedOrder = [
            'beforeAction',
            'action'
        ];

        $order = [];
        $beforeActionMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeAction';
        });

        $routingMiddleware = $this->getMockBuilder(RoutingMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $routingMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $actionMiddlewareStub = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'action';
        });

        $actionMiddleware = $this->getMockBuilder(ActionMiddleware::class, ['__invoke'])
            ->disableOriginalConstructor()
            ->getMock();

        $actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($actionMiddlewareStub));

        $responderMiddleware = $this->getMockBuilder(ResponderMiddleware::class)
            ->disableOriginalConstructor()
            ->getMock();

        $app = new Adrenaline($routingMiddleware, $actionMiddleware, $responderMiddleware, $this->emitter);

        $app->beforeAction($beforeActionMiddleware);
        $app($this->request, $this->response);
        $this->assertEquals($order, $expectedOrder);
    }


    /**
     * @test
     */
    public function beforeEmitterMiddlewareWillBeCalledBeforeEmitter()
    {
        $expectedOrder = [
            'beforeEmitter',
            'emitter'
        ];

        $order = [];

        $routingMiddleware = $this->getMockBuilder(RoutingMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $routingMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));


        $actionMiddleware = $this->getMockBuilder(ActionMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $actionMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $responderMiddleware = $this->getMockBuilder(ResponderMiddleware::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $responderMiddleware->expects($this->any())
            ->method('__invoke')
            ->will($this->returnCallback($this->createTestMiddleware()));

        $beforeEmitterMiddleware = $this->createTestMiddleware(function () use (&$order) {
            $order[] = 'beforeEmitter';
        });

        $emitter = $this->getMock(EmitterInterface::class, ['emit']);
        $emitter->expects($this->once())
            ->method('emit')
            ->will($this->returnCallback(function () use (&$order) {
                $order[] = 'emitter';
            }));

        $app = new Adrenaline($routingMiddleware, $actionMiddleware, $responderMiddleware, $emitter);

        $app->beforeEmitter($beforeEmitterMiddleware);

        $app($this->request, $this->response);

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
        $app = Adrenaline::prototyping();
        $app->setDefaultRouteClass(\stdClass::class);
    }

    /**
     * @test
     */
    public function acceptsRouteAsDefaultRouteClass()
    {
        $thrown = false;

        try {
            $app = Adrenaline::prototyping();
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
            $app = Adrenaline::prototyping();
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
            $app = Adrenaline::prototyping();
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

        $router = $this->createRouteCreationTestRouter(strtoupper('GET'), $name, $path, $target, $matchers, $defaultRouteClass);
        $app = Adrenaline::lenient([], [], $router, $this->emitter);
        $app->setDefaultRouteClass(DeeplyInheritedRoute::class);
        $app->get($name, $path, $target, $matchers);
    }

    /**
     * @test
     */
    public function createDefaultCreatesAdrenaline()
    {
        $app = Adrenaline::createDefault('routing', [], []);
        $this->assertInstanceOf(Adrenaline::class, $app);
    }

    /**
     * @test
     */
    public function outGetsCalledIfGivenAndNoErrorHandlerIsSet()
    {
        $called = false;;
        $app = Adrenaline::lenient([], [], null, $this->emitter);
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

        $app = Adrenaline::lenient([$resolver], [], null, $this->emitter);
        $app->addRoute(
            Route::get('/')->to(function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response;
            })->named('home')
        );

        $app->setErrorHandler(function (ServerRequestInterface $request, ResponseInterface $response, $err) use (&$called) {
            $called[] = 'errCalled';
            return $response;
        });

        $app($this->request, $this->response, function (ServerRequestInterface $request, ResponseInterface $response) use (&$called) {
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

        $app = Adrenaline::lenient([$resolver], [], null, $this->emitter);

        $app->setErrorHandler(function (ServerRequestInterface $request, ResponseInterface $response, $err) use (&$called) {
            $called[] = 'errCalled';
            return $response;
        });

        $app($this->request, $this->response, function (ServerRequestInterface $request, ResponseInterface $response) use (&$called) {
            $called[] = 'outCalled';
            return $response;
        });

        $this->assertSame($expected, $called);
    }

    /**
     * Returns a middleware. You may define an additional callable which will be executed in front of the default behavior
     * for testing purpose (e.g. testing call order)
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
        $app = Adrenaline::lenient([], [], $router, $this->emitter);
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
            ) use ($self, $method, $name, $path, $target, $matchers, $class) {
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

    /**
     * @test
     */
    public function strictReturnsAnAdrenalineInstance()
    {
        $adrenaline = Adrenaline::strict([], []);
        $this->assertInstanceOf(Adrenaline::class, $adrenaline);
    }
}
