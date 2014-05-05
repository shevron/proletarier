<?php

namespace Proletarier;

use Proletarier\Message\Request;
use Zend\Mvc\Router\RouteInterface;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\RequestInterface as StdRequest;

class RouteStack implements RouteInterface
{
    protected $routes = array();

    /**
     * Create a new route with given options.
     *
     * @param  array|\Traversable $options
     *
     * @return RouteStack
     */
    public static function factory($options = array())
    {
        $route = new static(); /* @var $route RouteStack */
        if (isset($options['routes'])) {
            $route->addRoutes($options['routes']);
        }

        return $route;
    }

    public function addRoutes($routes)
    {
        if (! (is_array($routes) || $routes instanceof \Traversable)) {
            throw new \InvalidArgumentException("\$routes is expected to be an array or a Traversable object");
        }

        foreach ($routes as $name => $callable) {
            $this->addRoute($name, $callable);
        }
    }

    /**
     * @param string   $name
     * @param callable $callable
     *
     * @return $this
     */
    public function addRoute($name, callable $callable)
    {
        $this->routes[$name] = $callable;
        return $this;
    }

    /**
     * Match a given request.
     *
     * @param \Proletarier\Message\Request|\Zend\Stdlib\RequestInterface $request
     * @throws \InvalidArgumentException
     * @return RouteMatch
     */
    public function match(StdRequest $request)
    {
        if (! $request instanceof Request) {
            throw new \InvalidArgumentException("Unexpected request type: expecting a Proletarier\\Request object");
        }

        $action = $request->getAction();
        if (isset($this->routes[$action])) {
            $match = new RouteMatch(array(
                'controller' => $this->routes[$action]
            ));
            $match->setMatchedRouteName($action);
            return $match;
        }
    }

    /**
     * Assemble the route.
     *
     * @param  array $params
     * @param  array $options
     *
     * @return mixed
     */
    public function assemble(array $params = array(), array $options = array())
    {
        if (isset($params['action']) && isset($this->routes[$params['action']])) {
            return $params['action'];
        } else {
            return null;
        }
    }
}
