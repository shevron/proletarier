<?php

namespace Proletarier\Message;

class Response extends AbstractMessage implements ResponseInterface
{
    const STATUS_OK     = 'OK';
    const STATUS_FAILED = 'FAILED';
    const STATUS_ERROR  = 'ERROR';

    protected $status;

    public function __construct($status = self::STATUS_OK, $params = array())
    {
        $this->setStatus($status);
        $this->setParameters($params);
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Serialize this message into a string
     *
     * @throws InvalidMessageException
     * @return string
     */
    public function serialize()
    {
        if (! $this->status) {
            throw new InvalidMessageException("Response cannot be serialized, must contain a status");
        }

        return json_encode(array(
            'status' => $this->status,
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
            throw new InvalidMessageException("Invalid Response: doesn't seem to be a json-serialized object");
        }

        if (! (isset($data['status']) && isset($data['params']))) {
            throw new InvalidMessageException("Invalid Response: status or params fields are missing");
        }

        $this->status = $data['status'];
        $this->setParameters($data['params']);

        return $this;
    }

}
