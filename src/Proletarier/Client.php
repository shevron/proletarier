<?php

namespace Proletarier;

use Zend\EventManager\EventInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZMQContext;
use ZMQSocket;
use ZMQ;

class Client
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
        $socket = $this->connect();

        if (is_string($event)) {
            $event = new Event($event);
        }

        if (! $event instanceof EventInterface) {
            throw new \InvalidArgumentException("Expecting either a string or an EventInterface");
        }

        if (! empty($params)) {
            $event->setParams($params);
        }

        $socket->send(Event::toJson($event));
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
            $this->socket->connect($this->connect);
        }

        return $this->socket;
    }

    /**
     * @param ServiceLocatorInterface $locator
     *
     * @return Client
     * @throws \ErrorException
     */
    public static function factory(ServiceLocatorInterface $locator)
    {
        $config = $locator->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration is missing the 'proletarier' key");
        }

        $connect = $config['proletarier']['client']['connect'];
        if (! $connect) {
            // Fall back: connect to the broker bind address
            $connect = $config['proletarier']['broker']['bind'];
        }

        /* @var $client Client */
        $client = new static($connect);
        return $client;
    }
}
