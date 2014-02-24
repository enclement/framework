<?php

namespace TylerSommer\Nice;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ScopeInterface;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\HttpKernel;

class Application extends HttpKernel implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param ContainerInterface          $container
     * @param EventDispatcherInterface    $dispatcher
     * @param ControllerResolverInterface $resolver
     * @param RequestStack                $requestStack
     *
     * @internal param callable $routeFactory
     */
    public function __construct(
        ContainerInterface $container = null,
        EventDispatcherInterface $dispatcher = null,
        ControllerResolverInterface $resolver = null,
        RequestStack $requestStack = null
    ) {
        $this->container = $container = $container ?: new ContainerBuilder();

        $dispatcher = $dispatcher ?: new ContainerAwareEventDispatcher($container);
        $resolver   = $resolver ?: new ControllerResolver();

        parent::__construct($dispatcher, $resolver, $requestStack);

        $container->register('router.parser', 'FastRoute\RouteParser\Std');
        $container->register('router.data_generator', 'FastRoute\DataGenerator\GroupCountBased');
        $container->register('router.collector', 'FastRoute\RouteCollector')
            ->addArgument(new Reference('router.parser'))
            ->addArgument(new Reference('router.data_generator'));

        $container->register('routes', 'Closure')
            ->setSynthetic(true);

        $container->register('router.dispatcher_factory', 'TylerSommer\Nice\Router\DispatcherFactory\GroupCountBasedFactory')
            ->addArgument(new Reference('router.collector'))
            ->addArgument(new Reference('routes'));

        $container->register('router.dispatcher', 'FastRoute\Dispatcher')
            ->setFactoryService('router.dispatcher_factory')
            ->setFactoryMethod('create');

        $container->register('router.dispatcher_subscriber', 'TylerSommer\Nice\Router\RouterSubscriber')
            ->addArgument(new Reference('router.dispatcher'));

        $dispatcher->addSubscriberService('router.dispatcher_subscriber', 'TylerSommer\Nice\Router\RouterSubscriber');
    }

    /**
     * Helper method to get things going.
     *
     * Inspired by Silex
     */
    public function run()
    {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets a service.
     *
     * @param string          $id      The service identifier
     * @param object|callable $service The service instance
     * @param string          $scope   The scope of the service
     */
    public function set($id, $service, $scope = self::SCOPE_CONTAINER)
    {
        $this->container->set($id, $service, $scope);
    }

    /**
     * Gets a service.
     *
     * @param string $id              The service identifier
     * @param int    $invalidBehavior The behavior when the service does not exist
     *
     * @return object The associated service
     *
     * @throws InvalidArgumentException if the service is not defined
     * @throws ServiceCircularReferenceException When a circular reference is detected
     * @throws ServiceNotFoundException When the service is not defined
     *
     * @see Reference
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        return $this->container->get($id, $invalidBehavior);
    }

    /**
     * Returns true if the given service is defined.
     *
     * @param string $id The service identifier
     *
     * @return bool true if the service is defined, false otherwise
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * Gets a parameter.
     *
     * @param string $name The parameter name
     *
     * @return mixed  The parameter value
     *
     * @throws InvalidArgumentException if the parameter is not defined
     */
    public function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * Checks if a parameter exists.
     *
     * @param string $name The parameter name
     *
     * @return bool The presence of parameter in container
     */
    public function hasParameter($name)
    {
        return $this->container->hasParameter($name);
    }

    /**
     * Sets a parameter.
     *
     * @param string $name  The parameter name
     * @param mixed  $value The parameter value
     */
    public function setParameter($name, $value)
    {
        $this->container->setParameter($name, $value);
    }

    /**
     * Enters the given scope
     *
     * @param string $name
     */
    public function enterScope($name)
    {
        $this->container->enterScope($name);
    }

    /**
     * Leaves the current scope, and re-enters the parent scope
     *
     * @param string $name
     */
    public function leaveScope($name)
    {
        $this->container->leaveScope($name);
    }

    /**
     * Adds a scope to the container
     *
     * @param ScopeInterface $scope
     */
    public function addScope(ScopeInterface $scope)
    {
        $this->container->addScope($scope);
    }

    /**
     * Whether this container has the given scope
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasScope($name)
    {
        return $this->container->hasScope($name);
    }

    /**
     * Determines whether the given scope is currently active.
     *
     * It does however not check if the scope actually exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isScopeActive($name)
    {
        return $this->container->isScopeActive($name);
    }
}