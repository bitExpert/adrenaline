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

namespace bitExpert\Adrenaline;

use bitExpert\Adrenaline\Domain\DomainPayload;

/**
 * Unit test for {@link \bitExpert\Adrenaline\Domain\DomainPayload}.
 */
class DomainPayloadUnitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function willReturnTypeGivenWhenInstanceWasCreated()
    {
        $domainPayload = new DomainPayload('test');

        $this->assertEquals('test', $domainPayload->getType());
    }

    /**
     * @test
     */
    public function willReturnModelGivenWhenInstanceWasCreated()
    {
        $model = ['a' => '1', 'b' => 2];
        $domainPayload = new DomainPayload('test', $model);

        $this->assertEquals($model, $domainPayload->getValues());
    }

    /**
     * @test
     */
    public function queryingModelAttributeWillReturnSetValue()
    {
        $model = ['a' => '1', 'b' => 2];
        $domainPayload = new DomainPayload('test', $model);

        $this->assertEquals($model['b'], $domainPayload->getValue('b'));
    }

    /**
     * @test
     */
    public function queryingNonExistentModelAttributeWillReturnNull()
    {
        $domainPayload = new DomainPayload('test', ['a' => '1', 'b' => 2]);

        $this->assertNull($domainPayload->getValue('c'));
    }

    /**
     * @test
     */
    public function modifiedSingleAttributeWillBeReturnedCorrectly()
    {
        $domainPayload = new DomainPayload('test', ['a' => '1', 'b' => 2]);
        $valueDomainPayload = $domainPayload->withValue('b', 3);

        $this->assertEquals(3, $valueDomainPayload->getValue('b'));
        $this->assertNotSame($domainPayload, $valueDomainPayload);
    }

    /**
     * @test
     */
    public function modifiedAttributeCollectionWillBeReturnedCorrectly()
    {
        $domainPayload = new DomainPayload('test', ['a' => '1', 'b' => 2]);
        $valuesDomainPayload = $domainPayload->withValues(['b' => 4, 'c' => 'd']);

        $this->assertEquals(4, $valuesDomainPayload->getValue('b'));
        $this->assertEquals('d', $valuesDomainPayload->getValue('c'));
        $this->assertNotSame($domainPayload, $valuesDomainPayload);
    }

    /**
     * @test
     */
    public function setStatusReturnsNewInstanceWithStatus()
    {
        $status = 1000;
        $payload = new DomainPayload('test', []);
        $statusPayload = $payload->withStatus($status);

        $this->assertEquals($status, $statusPayload->getStatus());
        $this->assertNotSame($payload, $statusPayload);
    }

    /**
     * @test
     */
    public function jsonSerializeReturnsDataOnly()
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        
        $payload = new DomainPayload('test', $data);
        $this->assertSame($data, $payload->jsonSerialize());
    }
}
