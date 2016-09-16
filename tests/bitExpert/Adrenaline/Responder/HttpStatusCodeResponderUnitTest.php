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
 * Unit test for {@link \bitExpert\Adrenaline\Responder\HttpStatusCodeResponder}.
 */
class HttpStatusCodeResponderUnitTest extends \PHPUnit_Framework_TestCase
{
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
    }

    /**
     * @test
     */
    public function responseCodeIsPassedToResponseObject()
    {
        $responder = new HttpStatusCodeResponder(200);
        $domainPayload = new DomainPayload('test');
        /** @var ResponseInterface $response */
        $response = $responder($domainPayload, $this->response);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function nonIntegerResponseCodeWillThrowAnException()
    {
        $domainPayload = new DomainPayload('test');
        $responder = new HttpStatusCodeResponder('hello');

        $responder($domainPayload, $this->response);
    }
}
