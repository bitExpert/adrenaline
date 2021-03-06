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
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Responder\JsonResponder}.
 */
class JsonResponderUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonResponder
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

        $this->response = new Response();
        $this->responder = new JsonResponder();
    }

    /**
     * @test
     */
    public function responderConvertsModelToJson()
    {
        $model = ['1' => 'demo', '2a' => 'test'];
        $domainPayload = new DomainPayload('test', $model);
        $responder = $this->responder;

        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);
        $response->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertJson($response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function additionalHttpHeadersGetAppendedToResponse()
    {
        $domainPayload = new DomainPayload('test');
        $this->responder->setHeaders(['X-Sender' => 'PHPUnit Testcase']);
        $responder = $this->responder;
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('X-Sender'));
    }

    /**
     * @test
     */
    public function contentTypeCantBeChanged()
    {
        $domainPayload = new DomainPayload('test');
        $this->responder->setHeaders(['Content-Type' => 'my/type']);
        $responder = $this->responder;
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
    }

    /**
     * @test
     */
    public function respectsStatusCodeSetInPayload()
    {
        $domainPayload = (new DomainPayload('test'))->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
        $responder = $this->responder;
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function usesOkStatusCodeIfNoneSetInPayload()
    {
        $domainPayload = (new DomainPayload('test'));
        $responder = $this->responder;
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }
}
