<?php

/**
 * This file is part of the Adrenaline framework.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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
    public function __construct($type, array $data = array())
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function withValue($key, $value = null)
    {
        $new = clone $this;
        $new->data[$key] = $value;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withValues(array $values)
    {
        $new = clone $this;
        foreach ($values as $property => $value) {
            $new->data[$property] = $value;
        }

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($status)
    {
        $new = clone $this;

        $new->status = $status;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return $this->getValues();
    }
}
