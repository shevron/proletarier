<?php

namespace Proletarier\Message;

abstract class AbstractMessage implements MessageInterface
{
    protected $parameters = array();

    /**
     * Set content
     *
     * @param  mixed $content
     *
     * @return mixed
     */
    public function setContent($content)
    {
        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        $this->setParameters($content);
    }

    /**
     * Get content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function getParameter($key, $default = null)
    {
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        } else {
            return $default;
        }
    }
}
