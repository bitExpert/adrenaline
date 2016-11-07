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

namespace bitExpert\Adrenaline\Domain;

use bitExpert\Adroit\Domain\Payload;

/**
 * The domain payload object represents the domain's data it's state and type
 *
 * @api
 */
class DomainPayload implements Payload, \JsonSerializable
{
    /**
     * @var mixed
     */
    private $status;
    /**
     * @var mixed
     */
    private $type;
    /**
     * @var array
     */
    private $data;

    /**
     * Creates a new {@link \bitExpert\Adroit\Domain\DomainPayload}.
     *
     * @param mixed $type
     * @param array $data
     */
    public function __construct($type, array $data = [])
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Gets the value for $key. If no value is set for $key it will return $default or null.
     *
     * @return mixed
     */
    public function getValue($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Returns all the values.
     */
    public function getValues() : array
    {
        return $this->data;
    }

    /**
     * Returns a {@link \bitExpert\Adroit\Domain\Payload\DomainPayload} clone
     * with $key set to $value.
     *
     * @return DomainPayload
     */
    public function withValue($key, $value = null) : self
    {
        $new = clone $this;
        $new->data[$key] = $value;

        return $new;
    }

    /**
     * Returns a {@link \bitExpert\Adroit\Domain\Payload\DomainPayload} clone
     * with new $values. $values is an array containing the new $property =>
     * $value relationships.
     *
     * @return DomainPayload
     */
    public function withValues(array $values) : self
    {
        $new = clone $this;
        foreach ($values as $property => $value) {
            $new->data[$property] = $value;
        }

        return $new;
    }

    /**
     * Returns a {@link \bitExpert\Adroit\Domain\Payload\DomainPayload} clone
     * with status set to $sttaus
     *
     * @return DomainPayload
     */
    public function withStatus($status) : self
    {
        $new = clone $this;

        $new->status = $status;
        return $new;
    }

    /**
     * Returns the status.
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->getValues();
    }
}
