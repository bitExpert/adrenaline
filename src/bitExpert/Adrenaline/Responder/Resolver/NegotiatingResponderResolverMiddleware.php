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

namespace bitExpert\Adrenaline\Responder\Resolver;

use bitExpert\Adrenaline\Accept\ContentNegotiationService;
use Psr\Http\Message\ServerRequestInterface;
use bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware;

/**
 * The ContentNegotiatingResponderResolver does not resolve responders itself, but
 * delegates to the configured $responderResolvers. This responder resolver uses
 * the requested media type to select a suitable Responder for a request. The
 * requested media type is determined through the configured
 * {@link \bitExpert\Adroit\Accept\ContentNegotiationManager}.
 *
 * @api
 */
class NegotiatingResponderResolverMiddleware extends ResponderResolverMiddleware
{
    /**
     * @var \bitExpert\Adroit\Responder\Resolver\ResponderResolver[]
     */
    protected $responderResolvers;
    /**
     * @var ContentNegotiationService
     */
    protected $negotiationService;

    /**
     * Creates a new {@link \bitExpert\Adrenaline\Responder\Resolver\NegotiatingResponderResolver}.
     * @param \bitExpert\Adroit\Responder\Resolver\ResponderResolver[] $resolvers
     * @param string $domainPayloadAttribute
     * @param string $responderAttribute
     * @param ContentNegotiationService $negotiationService
     * @throws \InvalidArgumentException
     */
    public function __construct(
        array $resolvers,
        $domainPayloadAttribute,
        $responderAttribute,
        ContentNegotiationService $negotiationService
    ) {
    
        parent::__construct($resolvers, $domainPayloadAttribute, $responderAttribute);
        $this->negotiationService = $negotiationService;
    }

    /**
     * @inheritdoc
     */
    protected function getApplicableResolvers(ServerRequestInterface $request)
    {
        $availableTypes = array_keys($this->resolvers);
        $type = $this->negotiationService->getBestMatch($request, $availableTypes);

        $resolvers = [];
        if (array_key_exists($type, $this->resolvers)) {
            $resolvers = $this->resolvers[$type];
            if (!is_array($resolvers)) {
                $resolvers = [$resolvers];
            }
        }
        return $resolvers;
    }
}
