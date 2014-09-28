<?php

namespace Proletarier\Client;

use Proletarier\Event;
use Zend\EventManager\EventInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZMQContext;
use ZMQSocket;
use ZMQ;

class Client implements ClientInterface
{
    /**
     * @var $string string Connection address
     */
    protected $connect;

    protected $context;

    protected $socket;

    public function __construct($connect)
    {
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

        $this->send(Event::toJson($event));
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
