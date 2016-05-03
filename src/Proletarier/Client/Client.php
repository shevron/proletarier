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

namespace Proletarier\Client;

use Proletarier\Event\Event;
use Zend\EventManager\EventInterface;
use Zend\Serializer\Adapter\AdapterInterface as SerializerInterface;
use Zend\Serializer\Adapter\Json;
use ZMQContext;
use ZMQSocket;
use ZMQ;

class Client implements ClientInterface
{
    /**
     * @var string Connection address
     */
    protected $connect;

    /**
     * @var ZMQContext
     */
    protected $context;

    /**
     * @var ZMQSocket
     */
    protected $socket;

    /**
     * Serliazer object
     *
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct($connect, SerializerInterface $serializer = null)
    {
        if (! $serializer) {
            $serializer = new Json();
        }
        $this->serializer = $serializer;
        $this->connect = $connect;
    }

    /**
     * Trigger an asynchronous event
     *
     * @param EventInterface | string $event
     * @param array                   $params
     *
     * @throws \InvalidArgumentException
     */
    public function trigger($event, array $params = array())
    {
        if (is_string($event)) {
            $event = new Event($event);
        }

        if (! $event instanceof EventInterface) {
            throw new \InvalidArgumentException("Expecting either a string or an EventInterface");
        }

        if (! empty($params)) {
            $event->setParams($params);
        }

        $payload = $this->serializer->serialize($event);
        $this->send($payload);
    }

    /**
     * Send a message over the socket
     *
     * @param $message
     */
    protected function send($message)
    {
        $socket = $this->connect();
        $socket->send($message);
    }

    /**
     * Lazy-connect to the socket
     *
     * This can be called any number of times, it should have no effect if already connected.
     *
     * @return ZMQSocket
     */
    protected function connect()
    {
        if (! ($this->context && $this->socket)) {
            $this->context = new ZMQContext();
            $this->socket = new ZMQSocket($this->context, ZMQ::SOCKET_PUSH);
            $this->socket->setSockOpt(ZMQ::SOCKOPT_LINGER, 30);
            $this->socket->connect($this->connect);
        }

        return $this->socket;
    }
}
