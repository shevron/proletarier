<?php

namespace Proletarier\Message;

class Request extends \Zend\Stdlib\Request implements RequestInterface
{
    /**
     * Action to perform
     *
     * @var string
     */
    protected $action;

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Convert this request into a JSON-encoded string
     *
     * @return string
     */
    public function toString()
    {
        return json_encode(array(
            'action'   => $this->action,
            'metadata' => $this->metadata,
            'content'  => $this->content
        ));
    }

    /**
     * Convert a JSON-encoded string into a Request object
     *
     * @param  string $string
     *
     * @throws \InvalidArgumentException
     * @return Request
     */
    public static function fromString($string)
    {
        $req = new Request();
        $input = json_decode($string, true);
        if (! ($input && is_array($input))) {
            throw new \InvalidArgumentException("Unable to parse json-encoded request or invalid request format");
        }

        if (isset($input['action'])) {
            $req->setAction($input['action']);
        }

        if (isset($input['metadata'])) {
            $req->setMetadata($input['metadata']);
        }

        if (isset($input['content'])) {
            $req->setContent($input['content']);
        }

        return $req;
    }
}
