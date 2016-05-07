<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use bitExpert\Adroit\Domain\Payload;

class ForwardPayloadAction implements Action
{
    /**
     * @var Payload
     */
    protected $payload;

    public function __construct(Payload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->payload;
    }
}
