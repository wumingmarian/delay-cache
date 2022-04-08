<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Config\Config;
use Hyperf\Di\Aop\AbstractAspect;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Cache;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

abstract class AbstractDelayCacheAspect extends AbstractAspect
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var mixed|Cache
     */
    protected $cache;

    /**
     * AbstractDelayCacheAspect constructor.
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cache = $this->container->get(Cache::class);
    }

    /**
     * @param $annotation
     * @param $proceedingJoinPoint
     * @param $value
     * @throws ConfigureNotExistsException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function asyncJobPush($annotation, $proceedingJoinPoint, $value)
    {
        $config = $this->cache->getConfig($annotation->config);

        if ($annotation->driver) {
            $driver = $annotation->driver;
        } else {
            $driver = $config['driver'] ?? 'default';
        }

        $this->container->get(DriverFactory::class)->get($driver)->push(make($annotation->job, [
            $proceedingJoinPoint->className,
            $proceedingJoinPoint->methodName,
            $annotation,
            $value
        ]), $annotation->delay);
    }

    /**
     * @param $annotation
     * @param $proceedingJoinPoint
     * @return mixed
     */
    public function getValue($annotation, $proceedingJoinPoint)
    {
        if ($annotation->value) {
            $value = $proceedingJoinPoint->arguments['keys'][$annotation->value];
        } else {
            $value = $proceedingJoinPoint->getArguments()[0];
        }
        return $value;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isDispatchLoop($value)
    {
        return isset($value['__DISPATCH_LOOP__']) && $value['__DISPATCH_LOOP__'] === true;
    }
}