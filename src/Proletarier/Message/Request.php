<?php

namespace Proletarier\Message;


class Request extends AbstractMessage implements RequestInterface
{
    protected $action = null;

    public function __construct($action = null, $params = array())
    {
        $this->setAction($action);
        $this->setParameters($params);
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    /**
     * Serialize this message into a string
     *
     * @throws InvalidMessageException
     * @return string
     */
    public function serialize()
    {
        if (! $this->action) {
            throw new InvalidMessageException("Request cannot be serialized, must contain an action");
        }

        return json_encode(array(
            'action' => $this->action,
            'params' => $this->getParameters()
        ));
    }

    /**
     * Load a serialized string into this message object
     *
     * @param string $serialized
     * @throws InvalidMessageException
     * @return $this
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        if (! is_array($data)) {
            throw new InvalidMessageException("Invalid Request: doesn't seem to be a json-serialized object");
        }

        if (! (isset($data['action']) && isset($data['params']))) {
            throw new InvalidMessageException("Invalid Request: action or params fields are missing");
        }

        $this->action = $data['action'];
        $this->setParameters($data['params']);

        return $this;
    }

    /**
     * Create a request object from a string
     *
     * @param $string
     *
     * @return Request
     */
    static public function fromString($string)
    {
        $req = new static(); /* @var $req Request */
        $req->unserialize($string);
        return $req;
    }
}
