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

use Psr\Http\Message\MessageInterface;

/**
 * The ContentNegotiationManager parses the 'Accept' header of the request.
 *
 * @api
 */
interface ContentNegotiationService
{
    /**
     * Returns the "best match" of the given $priorities. Will return null in case
     * no match could be identified or a string containing the best matching Accept
     * header.
     *
     * @param MessageInterface $request
     * @param array $priorities A set of priorities.
     * @return null|string
     */
    public function getBestMatch(MessageInterface $request, array $priorities = array());
}
