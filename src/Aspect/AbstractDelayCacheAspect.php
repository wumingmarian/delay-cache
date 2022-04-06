<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Di\Aop\AbstractAspect;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Cache;

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
     * @param $fieldData
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function asyncJobPush($annotation, $proceedingJoinPoint, $fieldData)
    {
        $this->container->get(DriverFactory::class)->get($annotation->driver)->push(make($annotation->job, [
            $proceedingJoinPoint->className,
            $proceedingJoinPoint->methodName,
            $annotation,
            $fieldData
        ]), $annotation->delay);
    }

    /**
     * @param $annotation
     * @param $proceedingJoinPoint
     * @return mixed
     */
    public function getFieldData($annotation, $proceedingJoinPoint)
    {
        if ($annotation->value) {
            $fieldData = $proceedingJoinPoint->arguments['keys'][$annotation->value];
        } else {
            $fieldData = $proceedingJoinPoint->getArguments()[0];
        }
        return $fieldData;
    }

    /**
     * @param $fieldData
     * @return bool
     */
    public function isDispatchLoop($fieldData)
    {
        return isset($fieldData['__DISPATCH_LOOP__']) && $fieldData['__DISPATCH_LOOP__'] === true;
    }
}