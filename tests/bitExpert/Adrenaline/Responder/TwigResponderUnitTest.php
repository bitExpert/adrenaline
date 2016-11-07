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

namespace bitExpert\Adrenaline\Responder;

use bitExpert\Adrenaline\Domain\DomainPayload;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Responder\TwigResponder}.
 */
class TwigResponderUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \bitExpert\Adrenaline\Domain\DomainPayload
     */
    protected $domainPayload;
    /**
     * @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $twig;
    /**
     * @var TwigResponder
     */
    protected $responder;
    /**
     * @var Response
     */
    protected $response;

    /**
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $this->twig = $this->createMock('\Twig_Environment');
        $this->domainPayload = new DomainPayload('test');
        $this->responder = new TwigResponder($this->twig);
        $this->response = new Response();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function passingAnArrayAsTemplateWillThrowAnException()
    {
        $this->responder->setTemplate(array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function passingAnIntAsTemplateWillThrowAnException()
    {
        $this->responder->setTemplate(2);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function callingBuildResponseWithoutAPresetTemplateWillThrowAnException()
    {
        $this->responder->__invoke($this->domainPayload, $this->response);
    }

    /**
     * @test
     */
    public function callingBuildResponseWithAPresetTemplateWillReturnResponse()
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->will($this->returnValue('<html>'));

        $this->responder->setTemplate('mytemplate.twig');
        /** @var ResponseInterface $response */
        $response = $this->responder->__invoke($this->domainPayload, $this->response);
        $response->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['text/html'], $response->getHeader('Content-Type'));
        $this->assertEquals('<html>', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function additionalHttpHeadersGetAppendedToResponse()
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->will($this->returnValue('<html>'));

        $this->responder->setTemplate('mytemplate.twig');
        $this->responder->setHeaders(['X-Sender' => 'PHPUnit Testcase']);
        $responder = $this->responder;
        $response = $responder($this->domainPayload, $this->response);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['text/html'], $response->getHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('X-Sender'));
    }

    /**
     * @test
     */
    public function contentTypeCantBeChanged()
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->will($this->returnValue('<html>'));

        $this->responder->setTemplate('mytemplate.twig');
        $this->responder->setHeaders(['Content-Type' => 'my/type']);
        $response = $this->responder->__invoke($this->domainPayload, $this->response);

        $this->assertEquals(['text/html'], $response->getHeader('Content-Type'));
    }

    /**
     * @test
     */
    public function respectsStatusCodeSetInPayload()
    {
        $domainPayload = (new DomainPayload('test'))->withStatus(400);
        $responder = $this->responder;
        $responder->setTemplate('mytemplate.twig');
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function usesOkStatusCodeIfNoneSetInPayload()
    {
        $domainPayload = (new DomainPayload('test'));
        $responder = $this->responder;
        $responder->setTemplate('mytemplate.twig');
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
