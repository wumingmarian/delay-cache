<?php

declare(strict_types=1);


namespace Wumingmarian\DelayCache;


use Hyperf\AsyncQueue\Driver\DriverFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

class DispatchLoop
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var mixed|Cache
     */
    protected $cache;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cache = $this->container->get(Cache::class);
    }

    /**
     * @param $value
     * @return bool
     */
    public function isDispatchLoop($value)
    {
        return isset($value['__DISPATCH_LOOP__']) && $value['__DISPATCH_LOOP__'] === true;
    }

    /**
     * @param $callable
     * @param $value
     * @return false|mixed
     */
    public function isExit($callable, $value)
    {
        if (is_array($callable)) {
            [$class, $method] = $callable;
        }

        if (is_string($callable) && strstr($callable, '@')) {
            [$class, $method] = explode('@', $callable);
        }

        if (isset($class) && isset($method)) {
            if (!class_exists($class) || !method_exists($class, $method)) {
                return false;
            }
            return (new $class)->{$method}($value);
        }

        if ($callable instanceof \Closure) {
            return $callable($value);
        }

        if (function_exists($callable)) {
            return $callable($value);
        }

        return false;
    }

    /**
     * @param $annotation
     * @param $proceedingJoinPoint
     * @param $value
     * @return bool
     * @throws ConfigureNotExistsException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function asyncJobPush($annotation, $proceedingJoinPoint, $value)
    {
        $config = $this->cache->getConfig($annotation->config);

        if ($this->isExit($config['exit_callable'], $value) === false) {
            $cacheKey = $this->cache->key($value, $annotation->config, $annotation->prefix);
            $this->cache->foreDel($cacheKey);
            return false;
        }

        $driver = $annotation->driver ?: ($config['driver'] ?? 'default');
        $delay = $annotation->delay ?: ($config['delay'] ?? 600);

        return $this->container->get(DriverFactory::class)->get($driver)->push(make($annotation->job, [
            $proceedingJoinPoint->className,
            $proceedingJoinPoint->methodName,
            $annotation,
            $value
        ]), $delay);
    }
}