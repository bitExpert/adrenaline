<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Responder;

use bitExpert\Adrenaline\Domain\DomainPayload;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Override realpath() in current namespace for testing
 *
 * @return string
 */
function realpath($path)
{
    return $path;
}

/**
 * Unit test for {@link \bitExpert\Adrenaline\Responder\TwigResponder}.
 */
class StaticHtmlFileResponderUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    protected $root;
    /**
     * @var StaticHtmlFileResponder
     */
    protected $responder;
    /**
     * @var ResponseInterface
     */
    protected $response;

    protected function setUp()
    {
        $this->root = new vfsStreamDirectory('test');

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot($this->root);

        $this->responder = new StaticHtmlFileResponder($this->root->url(), 'file');
        $this->response = new Response();
    }

    /**
     * @test
     */
    public function worksCorrectlyIfDomainPayloadAttributeIsSetAndPointsToExistingFile()
    {
        $content = 'The contents of the file';

        vfsStream::newFile('test.html')->at($this->root)->withContent($content);
        $payload = new DomainPayload('test', [StaticHtmlFileResponder::ATTRIBUTE_FILE => 'test']);
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->responder->__invoke($payload, $this->response);
        $response->getBody()->rewind();
        $this->assertEquals($content, $response->getBody()->getContents());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsExceptionIfPayloadAttributeIsNullOrNotPresent()
    {
        $payload = new DomainPayload(StaticHtmlFileResponder::ATTRIBUTE_FILE, ['page' => 'test']);
        $this->responder->__invoke($payload, $this->response);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsExceptionIfFileIsNotPresent()
    {
        $payload = new DomainPayload(StaticHtmlFileResponder::ATTRIBUTE_FILE, ['file' => 'test']);
        $this->responder->__invoke($payload, $this->response);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsExceptionIfFileIsNotReadable()
    {
        $content = 'The contents of the file';

        $file = vfsStream::newFile('test.html')->at($this->root)->withContent($content);
        $file->chmod(0000);
        $payload = new DomainPayload(StaticHtmlFileResponder::ATTRIBUTE_FILE, ['file' => 'test']);
        $this->responder->__invoke($payload, $this->response);
    }

    /**
     * @test
     */
    public function usesSetFileAttribute()
    {
        $fileAttribute = 'page';
        $content = 'The contents of the file';

        vfsStream::newFile('test.html')->at($this->root)->withContent($content);

        $payload = new DomainPayload('test', [$fileAttribute => 'test']);
        $this->responder->setFileAttribute($fileAttribute);
        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->responder->__invoke($payload, $this->response);
        $response->getBody()->rewind();
        $this->assertEquals($content, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function usesDomainPayloadStatusCodeForResponse()
    {
        $statusCode = 500;

        vfsStream::newFile('test.html')->at($this->root);

        $payload = (new DomainPayload('test', [StaticHtmlFileResponder::ATTRIBUTE_FILE => 'test']));
        $payload = $payload->withStatus($statusCode);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->responder->__invoke($payload, $this->response);
        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function mergesAdditionalHeadersToResponse()
    {
        $expected = [
            'X-Test' => ['Test'],
            'Content-Type' => ['text/html']
        ];

        vfsStream::newFile('test.html')->at($this->root);

        $payload = (new DomainPayload('test', [StaticHtmlFileResponder::ATTRIBUTE_FILE => 'test']));
        $this->responder->setHeaders(['X-Test' => 'Test']);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->responder->__invoke($payload, $this->response);
        $this->assertEquals($expected, $response->getHeaders());
    }

    /**
     * @test
     */
    public function returnsStatus200PerDefault()
    {
        vfsStream::newFile('test.html')->at($this->root);

        $payload = (new DomainPayload('test', [StaticHtmlFileResponder::ATTRIBUTE_FILE => 'test']));

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->responder->__invoke($payload, $this->response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function returnsCorrectContentTypeHeader()
    {
        $expected = [
            'Content-Type' => ['text/html']
        ];

        vfsStream::newFile('test.html')->at($this->root);

        $payload = (new DomainPayload('test', [StaticHtmlFileResponder::ATTRIBUTE_FILE => 'test']));

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->responder->__invoke($payload, $this->response);
        $this->assertEquals($expected, $response->getHeaders());
    }
}