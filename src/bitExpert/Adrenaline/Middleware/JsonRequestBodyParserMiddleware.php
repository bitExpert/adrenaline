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

namespace bitExpert\Adrenaline\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonRequestBodyParserMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null) : ResponseInterface
    {
        $contentType = $request->getHeader('Content-Type');
        $contentType = isset($contentType[0]) ? $contentType[0] : null;
        
        if ($contentType && (0 === strpos(strtolower($contentType), 'application/json'))) {
            $body = (string) $request->getBody();

            if (!empty($body)) {
                $parsed = @json_decode($body, true);

                if (null === $parsed) {
                    throw new RequestBodyParseException(sprintf(
                        'Unable to parse json request body: %s',
                        json_last_error_msg()
                    ), json_last_error());
                }

                $request = $request->withParsedBody($parsed);
            }
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }
}
