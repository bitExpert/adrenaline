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
 * Unit test for {@link \bitExpert\Adrenaline\Action\AbstractAction}.
 */
class AbstractActionUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function createPayloadCreatesPayloadWithGivenValues()
    {
        $type = 'test';
        $data = [
            'key' => 'value'
        ];
        $action = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class = new \ReflectionClass($action);
        $method = $class->getMethod('createPayload');
        $method->setAccessible(true);
        /** @var DomainPayload $payload */
        $payload = $method->invokeArgs($action, [$type, $data]);
        $this->assertInstanceOf(DomainPayload::class, $payload);
        $this->assertEquals($type, $payload->getType());
        $this->assertEquals($data, $payload->getValues());
    }
}
