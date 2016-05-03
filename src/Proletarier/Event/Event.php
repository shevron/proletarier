<?php

/**
 *    Copyright 2016 Shahar Evron
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
