<?php

namespace Proletarier;

use Proletarier\Message\Request;
use ZMQContext;
use ZMQSocket;
use ZMQ;

class Client
{
    use EventManagerAwareTrait;

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
     * Send request
     *
     * @param Request $request
     */
    public function send(Request $request)
    {
        $socket = $this->connect();
        $socket->send($request->serialize());
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
}
