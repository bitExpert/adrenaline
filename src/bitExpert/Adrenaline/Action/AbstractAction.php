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

namespace bitExpert\Adrenaline\Action;

use bitExpert\Adrenaline\Domain\DomainPayload;
    
/**
 * Base class for all actions providing a default implementation of the __invoke()
 * method. Child classes need only to implement the execute() method.
 *
 * @api
 */
abstract class AbstractAction implements Action
{
    /**
     * Creates a domain payload instance of the given type with given data
     *
     * @param $type
     * @param array $data
     * @return DomainPayload
     */
    protected function createPayload($type, array $data = []) : DomainPayload
    {
        return new DomainPayload($type, $data);
    }
}
