<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Action\Resolver;

use bitExpert\Adrenaline\Action\ForwardPayloadAction;
use bitExpert\Adrenaline\Domain\DomainPayload;
use bitExpert\Adroit\Action\Resolver\CallableActionResolver;
use bitExpert\Pathfinder\Route;
use bitExpert\Pathfinder\RoutingResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;


/**
 * Unit test for {@link \bitExpert\Adrenaline\Action\AbstractAction}.
 */
class ActionResolverMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsAnExceptionWhenInStrictModeAndResultDoesNotImplementAction()
    {
        $resolver = new CallableActionResolver();

        $route = Route::create()->to(function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $routingResult = RoutingResult::forSuccess($route);

        $request = (new ServerRequest())->withAttribute('routing', $routingResult);
        $middleware = new ActionResolverMiddleware([$resolver], 'routing', 'payload', true);
        $middleware($request, new Response());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsAnExceptionIfAttributeDoesNotContainAResult()
    {
        $middleware = new ActionResolverMiddleware([], 'routing', 'payload');
        $middleware(new ServerRequest(), new Response());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsAnExceptionIfAttributeContainsAFailedResult()
    {
        $request = (new ServerRequest())->withAttribute('routing', RoutingResult::forFailure(0));
        $middleware = new ActionResolverMiddleware([], 'routing', 'payload');
        $middleware($request, new Response());
    }

    /**
     * @test
     */
    public function returnsActionWhenInStrictMode()
    {
        $resolver = new CallableActionResolver();
        $action = new ForwardPayloadAction(new DomainPayload('test'));

        $route = Route::create()->to($action);

        $routingResult = RoutingResult::forSuccess($route);

        $request = (new ServerRequest())->withAttribute('routing', $routingResult);
        $middleware = new ActionResolverMiddleware([$resolver], 'routing', 'payload', true);
        $middleware($request, new Response());
    }
}