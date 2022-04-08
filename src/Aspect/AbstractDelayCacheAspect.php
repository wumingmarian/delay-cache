<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Cache;
use Wumingmarian\DelayCache\DispatchLoop;

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
     * @var mixed|DispatchLoop
     */
    protected $dispatchLoop;

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
        $this->dispatchLoop = $this->container->get(DispatchLoop::class);
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
}