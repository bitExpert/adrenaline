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

use bitExpert\Adroit\Domain\Payload;
use Psr\Http\Message\ResponseInterface;

/**
 * Responder to convert the given model into JSON format.
 *
 * @api
 */
class StaticHtmlFileResponder implements Responder
{
    const ATTRIBUTE_FILE = 'file';

    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var string
     */
    protected $fileAttribute;
    /**
     * @var array
     */
    protected $headers;
    /**
     * @var string
     */
    protected $statusAttribute;

    public function __construct($basePath)
    {
        $this->basePath = realpath($basePath);
        $this->fileAttribute = self::ATTRIBUTE_FILE;
        $this->headers = [];
    }

    /**
     * Sets additional headers
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Sets the domain payload attribute name to get the filename from
     *
     * @param string $fileAttribute
     */
    public function setFileAttribute($fileAttribute)
    {
        $this->fileAttribute = $fileAttribute;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __invoke(Payload $payload, ResponseInterface $response)
    {
        /** @var \bitExpert\Adrenaline\Domain\DomainPayload $payload */
        $filename = $payload->getValue($this->fileAttribute, null);

        if (null === $filename) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a filename in domain payload attribute "%s"',
                $this->fileAttribute
            ));
        }

        $file = sprintf('%s/%s.html', $this->basePath, $filename);

        if (!file_exists($file) || !is_readable($file)) {
            throw new \RuntimeException(sprintf(
                'Could not find file "%s" or file is not readable',
                $file
            ));
        }

        $contents = file_get_contents($file);
        $response->getBody()->rewind();
        $response->getBody()->write($contents);

        $headers = array_merge($this->headers, ['Content-Type' => 'text/html']);

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        /** @var \bitExpert\Adrenaline\Domain\DomainPayload $payload */
        $status = $payload->getStatus() ?: 200;
        return $response->withStatus($status);
    }
}
