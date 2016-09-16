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

namespace bitExpert\Adrenaline\Accept;

use Negotiation\AcceptHeader;
use Negotiation\Negotiator;
use Psr\Http\Message\MessageInterface;

/**
 * The ContentNegotiationManager parses the 'Accept' header of the request.
 *
 * @api
 */
class ContentNegotiationManager implements ContentNegotiationService
{
    /**
     * @var Negotiator
     */
    protected $negotiator;

    /**
     * Creates a new {@link \bitExpert\Adrenaline\Accept\ContentNegotiationManager}.
     *
     * @param Negotiator $negotiator
     */
    public function __construct(Negotiator $negotiator)
    {
        $this->negotiator = $negotiator;
    }

    /**
     * @inheritdoc
     */
    public function getBestMatch(MessageInterface $request, array $priorities = [])
    {
        if (!$request->hasHeader('Accept')) {
            return null;
        }

        $header = $this->negotiator->getBest(implode(',', $request->getHeader('Accept')), $priorities);
        if ($header instanceof AcceptHeader) {
            /** @var AcceptHeader $header */
            return $header->getValue();
        }

        return null;
    }
}
