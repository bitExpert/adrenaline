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
use Psr\Http\Message\ResponseInterface;

/**
 * The HttpStatusCodeResponder creates a response for the given $statusCode. The
 * responder is used in cases where it is needed to return a simple status code response to the client,
 *
 * @api
 */
class HttpStatusCodeResponder implements Responder
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * Creates a new {@link \itExpert\Adroit\Responder\HttpStatusCodeResponder}.
     *
     * @param int $statusCode
     */
    public function __construct($statusCode)
    {
        $this->statusCode = (int) $statusCode;
    }

    /**
     * {@inheritDoc}
     * @throws \RuntimeException
     */
    public function __invoke(Payload $payload, ResponseInterface $response) : ResponseInterface
    {
        try {
            return $response->withStatus($this->statusCode);
        } catch (\Exception $e) {
            throw new \RuntimeException('Response object could not be instantiated! ' . $e->getMessage());
        }
    }
}
