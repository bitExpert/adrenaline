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

use bitExpert\Adrenaline\Domain\DomainPayload;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Action\AbstractAction}.
 */
class ForwardPayloadActionUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function forwardsGivenPayload()
    {
        $expectedPayload = new DomainPayload('type', ['key' => 'value']);
        $action = new ForwardPayloadAction($expectedPayload);
        $actualPayload = $action(new ServerRequest(), new Response());
        $this->assertSame($expectedPayload, $actualPayload);
    }
}