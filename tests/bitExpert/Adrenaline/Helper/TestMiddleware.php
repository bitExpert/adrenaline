<?php
/**
 * Created by PhpStorm.
 * User: phildenbrand
 * Date: 10.05.16
 * Time: 10:41
 */

namespace bitExpert\Adrenaline\Helper;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        if ($next) {
            $response = $next($request, $response);
        }
        return $response;
    }
}