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

namespace bitExpert\Adrenaline\Middleware;

use bitExpert\Adrenaline\Helper\TestBodyStub;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\PhpInputStream;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Middleware\JsonBodyParserMiddleware}.
 */
class JsonRequestBodyParserMiddlewareUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonRequestBodyParserMiddleware
     */
    protected $middleware;

    protected function setUp()
    {
        TestBodyStub::$body = null;
        TestBodyStub::$position = 0;
        stream_wrapper_register('testBody', TestBodyStub::class);
    }

    protected function tearDown()
    {
        stream_wrapper_unregister('testBody');
    }

    /**
     * @test
     */
    public function doesNotParseBodyIfContentTypeDoesNotMatchEvenIfBodyContainsJson()
    {
        TestBodyStub::$body = '{"test":"test"}';
        $parsedBody = 'RandomContent';

        $middleware = new JsonRequestBodyParserMiddleware();
        $request = new ServerRequest();
        $request = $request->withHeader('Content-Type', 'text/html');
        $stream = new PhpInputStream('testBody://whatever');

        $request = $request->withBody($stream);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) use (&$parsedBody) {
            $parsedBody = $request->getParsedBody();
            return $response;
        };

        $middleware($request, new Response(), $next);
        $this->assertEmpty($parsedBody);
    }

    /**
     * @test
     */
    public function doesParseBodyIfContentTypeMatchesAndBodyContainsJson()
    {
        $expected = [
            'test' => 'test'
        ];

        TestBodyStub::$body = json_encode($expected);
        $parsedBody = null;

        $middleware = new JsonRequestBodyParserMiddleware();
        $request = new ServerRequest();
        $request = $request->withHeader('Content-Type', 'application/json');
        $stream = new PhpInputStream('testBody://whatever');

        $request = $request->withBody($stream);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) use (&$parsedBody) {
            $parsedBody = $request->getParsedBody();
            return $response;
        };

        $middleware($request, new Response(), $next);
        $this->assertEquals($expected, $parsedBody);
    }

    /**
     * @test
     * @expectedException \bitExpert\Adrenaline\Middleware\RequestBodyParseException
     */
    public function throwsExceptionIfContentTypeMatchesAndBodyHasInvalidContents()
    {
        TestBodyStub::$body = '{invalid:json';
        $parsedBody = null;

        $middleware = new JsonRequestBodyParserMiddleware();
        $request = new ServerRequest();
        $request = $request->withHeader('Content-Type', 'application/json');
        $stream = new PhpInputStream('testBody://whatever');

        $request = $request->withBody($stream);
        $middleware($request, new Response());
    }
}
