<?php

/*
 * Copyright (c) Tyler Sommer
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Nice\Router\DispatcherFactory;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Nice\Router\DispatcherFactory;

class GroupCountBasedFactory implements DispatcherFactory
{
    /**
     * @var \FastRoute\RouteCollector
     */
    private $collector;

    /**
     * @var callable
     */
    private $routeFactory;

    /**
     * @var bool
     */
    private $collected = false;

    /**
     * Constructor
     *
     * @param RouteCollector $collector
     * @param callable       $routeFactory
     */
    public function __construct(RouteCollector $collector, $routeFactory)
    {
        $this->collector = $collector;
        $this->routeFactory = $routeFactory;
    }

    /**
     * Create a dispatcher
     *
     * @return Dispatcher
     */
    public function create()
    {
        if (!$this->collected) {
            call_user_func($this->routeFactory, $this->collector);

            $this->collected = true;
        }

        return new GroupCountBased($this->collector->getData());
    }
}
