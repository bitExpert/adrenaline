<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bitExpert\Adrenaline\Responder\Resolver;

use bitExpert\Adrenaline\Responder\Responder;

class ResponderResolverMiddleware extends \bitExpert\Adroit\Responder\Resolver\ResponderResolverMiddleware
{
    protected $strict;

    public function __construct($resolvers, $domainPayloadAttribute, $strict = false)
    {
        $this->strict = (bool) $strict;
        parent::__construct($resolvers, $domainPayloadAttribute);
    }

    protected function isValidResult($result)
    {
        if (!$this->strict) {
            return parent::isValidResult($result);
        } else {
            $valid = ($result instanceof Responder);
            if (!$valid) {
                throw new \RuntimeException(sprintf(
                    'Working in strict mode, therefore responders have to implement "%s"',
                    Responder::class
                ));
            }

            return true;
        }
    }
}