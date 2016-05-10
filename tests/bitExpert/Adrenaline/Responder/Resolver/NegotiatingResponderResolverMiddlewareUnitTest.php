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

use bitExpert\Adrenaline\Accept\ContentNegotiationManager;
use bitExpert\Adrenaline\Domain\DomainPayload;
use bitExpert\Adroit\Responder\Responder;
use bitExpert\Adroit\Responder\Resolver\ResponderResolver;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Responder\Resolver\NegotiatingResponderResolverMiddleware}.
 */
class NegotiatingResponderResolverMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var \bitExpert\Adrenaline\Domain\DomainPayload
     */
    protected $domainPayload;
    /**
     * @var ContentNegotiationManager
     */
    protected $manager;
    /**
     * @var ResponderResolver
     */
    protected $resolver1;
    /**
     * @var ResponderResolver
     */
    protected $resolver2;
    /**
     * @var Responder
     */
    protected $notAcceptedResponder;
    /**
     * @var NegotiatingResponderResolverMiddleware
     */
    protected $middleware;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = $this->getMock(ServerRequestInterface::class);
        $this->response = new Response();
        $this->domainPayload = new DomainPayload('');
        $this->manager = $this->getMockBuilder(ContentNegotiationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolver1 = $this->getMock(ResponderResolver::class);
        $this->resolver2 = $this->getMock(ResponderResolver::class);
        $mockedResolver = $this->getMockBuilder(ResponderResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolve'])
            ->getMock();

        $mockedResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue(null));

        $this->middleware = new NegotiatingResponderResolverMiddleware(
            [
                'text/html' => $this->resolver1,
                'text/vcard' => [
                    new \stdClass(),
                    new \stdClass()
                ],
                'application/json' =>
                    [
                        $mockedResolver,
                        $this->resolver2,
                        $this->resolver2
                    ]
            ],
            'domainPayload',
            Responder::class,
            $this->manager
        );
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Resolver\ResolveException
     */
    public function noBestMatchWillThrowResolveException()
    {
        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue(null));

        $request = (new ServerRequest())->withHeader('Accept', 'text/xml');

        $this->middleware->__invoke($request, new Response());
    }

    /**
     * @test
     * @expectedException \bitExpert\Adroit\Resolver\ResolveException
     */
    public function whenNoRespondersExistForBestMatchWillThrowResolveException()
    {
        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('application/custom'));

        $this->middleware->__invoke($this->request, $this->response);
    }

    /**
     * @test
     */
    public function oneConfiguredResponderForContentTypeWillBeUsed()
    {
        $setResponder = null;

        $responder = $this->getMock(Responder::class);

        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('text/html'));

        $this->resolver1->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($responder));

        $request = (new ServerRequest())->withAttribute('domainPayload', new DomainPayload(''));

        $next = function (ServerRequestInterface $request, ResponseInterface $response) use (&$setResponder) {
            $setResponder = $request->getAttribute(Responder::class);
        };

        $this->middleware->__invoke($request, $this->response, $next);
        $this->assertSame($responder, $setResponder);
    }

    /**
     * @test
     */
    public function firstMatchingResponderForContentTypeWillBeUsed()
    {
        $setResponder = null;

        $responder = $this->getMock(Responder::class);

        $this->manager->expects($this->once())
            ->method('getBestMatch')
            ->will($this->returnValue('application/json'));

        $this->resolver2->expects($this->once())
            ->method('resolve')
            ->will($this->returnValue($responder));

        $request = (new ServerRequest())->withAttribute('domainPayload', new DomainPayload(''));

        $next = function (ServerRequestInterface $request, ResponseInterface $response) use (&$setResponder) {
            $setResponder = $request->getAttribute(Responder::class);
        };

        $this->middleware->__invoke($request, $this->response, $next);
        $this->assertSame($responder, $setResponder);
    }
}
