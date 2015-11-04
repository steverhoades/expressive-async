<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * This class reworks the __invoke method to create an instance of ExpressiveAsync\Application
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */
namespace ExpressiveAsync\Container;

use Zend\Expressive\Container;
use Zend\Expressive\Container\ApplicationFactory as ExpressiveApplicationFactory;
use ExpressiveAsync\Application;
use Interop\Container\ContainerInterface;

class ApplicationFactory extends ExpressiveApplicationFactory
{
    /**
     * Create and return an Application instance.
     *
     * See the class level docblock for information on what services this
     * factory will optionally consume.
     *
     * @param ContainerInterface $container
     * @return Application
     */
    public function __invoke(ContainerInterface $container)
    {
        $router = $container->has(RouterInterface::class)
            ? $container->get(RouterInterface::class)
            : new FastRouteRouter();

        $finalHandler = $container->has('Zend\Expressive\FinalHandler')
            ? $container->get('Zend\Expressive\FinalHandler')
            : null;

        $emitter = $container->has(EmitterInterface::class)
            ? $container->get(EmitterInterface::class)
            : null;

        $app = new Application($router, $container, $finalHandler, $emitter);

        $this->injectPreMiddleware($app, $container);
        $this->injectRoutes($app, $container);
        $this->injectPostMiddleware($app, $container);

        return $app;
    }

    /**
     * Inject routes from configuration, if any.
     *
     * @param Application $app
     * @param ContainerInterface $container
     */
    private function injectRoutes(Application $app, ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['routes'])) {
            $app->pipeRoutingMiddleware();
            return;
        }

        foreach ($config['routes'] as $spec) {
            if (! isset($spec['path']) || ! isset($spec['middleware'])) {
                continue;
            }

            $methods = (isset($spec['allowed_methods']) && is_array($spec['allowed_methods']))
                ? $spec['allowed_methods']
                : null;
            $name    = isset($spec['name']) ? $spec['name'] : null;
            $methods = (null === $methods) ? Route::HTTP_METHOD_ANY : $methods;
            $route   = new Route($spec['path'], $spec['middleware'], $methods, $name);

            if (isset($spec['options']) && is_array($spec['options'])) {
                $route->setOptions($spec['options']);
            }

            $app->route($route);
        }
    }

    /**
     * Given a collection of middleware specifications, pipe them to the application.
     *
     * @param array $collection
     * @param Application $app
     * @param ContainerInterface $container
     * @throws Container\Exception\InvalidMiddlewareException for invalid middleware.
     */
    private function injectMiddleware(array $collection, Application $app, ContainerInterface $container)
    {
        foreach ($collection as $spec) {
            if (! array_key_exists('middleware', $spec)) {
                continue;
            }

            $path       = isset($spec['path']) ? $spec['path'] : '/';
            $middleware = $spec['middleware'];
            $error      = array_key_exists('error', $spec) ? (bool) $spec['error'] : false;
            $pipe       = $error ? 'pipeErrorHandler' : 'pipe';

            $app->{$pipe}($path, $middleware);
        }
    }

    /**
     * Inject middleware to pipe before the routing middleware.
     *
     * Pre-routing middleware is specified as the configuration subkey
     * middleware_pipeline.pre_routing.
     *
     * @param Application $app
     * @param ContainerInterface $container
     */
    private function injectPreMiddleware(Application $app, ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['middleware_pipeline']['pre_routing']) ||
            ! is_array($config['middleware_pipeline']['pre_routing'])
        ) {
            return;
        }

        $this->injectMiddleware($config['middleware_pipeline']['pre_routing'], $app, $container);
    }

    /**
     * Inject middleware to pipe after the routing middleware.
     *
     * Post-routing middleware is specified as the configuration subkey
     * middleware_pipeline.post_routing.
     *
     * @param Application $app
     * @param ContainerInterface $container
     */
    private function injectPostMiddleware(Application $app, ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['middleware_pipeline']['post_routing']) ||
            ! is_array($config['middleware_pipeline']['post_routing'])
        ) {
            return;
        }

        $this->injectMiddleware($config['middleware_pipeline']['post_routing'], $app, $container);
    }
}
