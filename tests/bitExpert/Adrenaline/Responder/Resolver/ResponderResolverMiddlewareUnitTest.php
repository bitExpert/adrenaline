<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Responder\Resolver;

use bitExpert\Adrenaline\Domain\DomainPayload;
use bitExpert\Adrenaline\Responder\HttpStatusCodeResponder;
use bitExpert\Adroit\Responder\Resolver\ResponderResolver;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Responder\Resolver\ResponderResolverMiddleware}.
 */
class ResponderResolverMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsAnExceptionWhenInStrictModeAndResponderDoesNotImplementInterface()
    {
        $responder = function () {

        };
        $resolver = $this->getMock(ResponderResolver::class, ['resolve']);
        $resolver->expects($this->any())
            ->method('resolve')
            ->with('test')
            ->will($this->returnValue($responder));

        $request = (new ServerRequest())->withAttribute('payload', new DomainPayload('test'));
        $middleware = new ResponderResolverMiddleware([$resolver], 'payload', true);
        $middleware($request, new Response());
    }

    /**
     * @test
     */
    public function returnsResponderWhenInStrictMode()
    {
        $responder = new HttpStatusCodeResponder(200);
        $resolver = $this->getMock(ResponderResolver::class, ['resolve']);
        $resolver->expects($this->any())
            ->method('resolve')
            ->with('test')
            ->will($this->returnValue($responder));

        $request = (new ServerRequest())->withAttribute('payload', new DomainPayload('test'));
        $middleware = new ResponderResolverMiddleware([$resolver], 'payload', true);
        $middleware($request, new Response());
    }
}