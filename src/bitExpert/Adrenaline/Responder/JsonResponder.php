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

use bitExpert\Adroit\Domain\Payload;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Responder to convert the given model into JSON format.
 *
 * @api
 */
class JsonResponder implements Responder
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Set additional HTTP headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __invoke(Payload $payload, ResponseInterface $response) : ResponseInterface
    {
        $response->getBody()->rewind();
        $response->getBody()->write(json_encode($payload));

        $headers = array_merge($this->headers, ['Content-Type' => 'application/json']);
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        /** @var \bitExpert\Adrenaline\Domain\DomainPayload $payload */
        $status = $payload->getStatus() ?: StatusCodeInterface::STATUS_OK;

        return $response->withStatus($status);
    }
}
