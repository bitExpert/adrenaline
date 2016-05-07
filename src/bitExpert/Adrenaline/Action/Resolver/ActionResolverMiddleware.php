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

use bitExpert\Adrenaline\Action\Action;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ActionResolverMiddleware which deals with {@link \bitExpert\Pathfinder\RoutingResult}
 */
class ActionResolverMiddleware extends \bitExpert\Adroit\Action\Resolver\ActionResolverMiddleware
{
    /**
     * @var bool
     */
    protected $strictMode;
    
    public function __construct($resolvers, $routingResultAttribute, $domainPayloadAttribute, $strictMode = false)
    {
        $this->strictMode = (bool) $strictMode;
        parent::__construct($resolvers, $routingResultAttribute, $domainPayloadAttribute);
    }

    /**
     * @inheritdoc
     */
    protected function getIdentifier(ServerRequestInterface $request)
    {
        /** @var \bitExpert\Pathfinder\RoutingResult $routingResult */
        $routingResult = parent::getIdentifier($request);
        if (empty($routingResult)) {
            throw new \RuntimeException(sprintf(
                'Could not fetch a routing result from request attribute "%s"',
                $this->routingResultAttribute
            ));
        }

        if ($routingResult->failed()) {
            return null;
        }

        return $routingResult->getRoute()->getTarget();
    }
    
    protected function isValidResult($result)
    {
        if (!$this->strictMode) {
            return parent::isValidResult($result);
        } else {
            $valid = ($result instanceof Action);
            if (!$valid) {
                throw new \RuntimeException(sprintf(
                    'Working in strict mode, therefore actions have to implement "%s"',
                    Action::class
                ));
            }

            return true;
        }
    }
}
