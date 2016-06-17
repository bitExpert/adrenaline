<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Action\Resolver;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Extends the behavior of the Adroit ActionResolverMiddleware to be able to deal with
 * Pathfinder RoutingResults. It extracts the target from the result's route and returns it
 * as identifier
 */
class ActionResolverMiddleware extends \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware
{
    /**
     * @inheritdoc
     */
    protected function getIdentifier(ServerRequestInterface $request)
    {
        /** @var \bitExpert\Pathfinder\RoutingResult $routingResult */
        $routingResult = parent::getIdentifier($request);

        if (!$routingResult || $routingResult->failed()) {
            return null;
        }

        return $routingResult->getRoute()->getTarget();
    }
}
