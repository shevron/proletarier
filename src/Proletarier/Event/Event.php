<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Event;

use Zend\Stdlib\ArraySerializableInterface;

class Event extends \Zend\EventManager\Event implements \JsonSerializable, ArraySerializableInterface
{
    /**
     * @return array
     */
    function jsonSerialize()
    {
        return $this->getArrayCopy();
    }

    /**
     * @param array $array
     * @return $this
     */
    public function exchangeArray(array $array)
    {
        if (isset($array['name'])) {
            $this->setName($array['name']);
        }
        if (isset($array['params'])) {
            $this->setParams($array['params']);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'name' => $this->getName(),
            'params' => $this->getParams()
        ];
    }
}
