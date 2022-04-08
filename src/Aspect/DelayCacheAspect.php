<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Annotation\DelayCache;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

/**
 * @Aspect
 */
class DelayCacheAspect extends AbstractDelayCacheAspect
{
    public $annotations = [
        DelayCache::class,
    ];

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConfigureNotExistsException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        /** @var DelayCache $annotation */
        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[DelayCache::class];
        $value = $this->getValue($annotation, $proceedingJoinPoint);

        if ($this->dispatchLoop->isDispatchLoop($value)) {
            if (false === $this->dispatchLoop->asyncJobPush($annotation, $proceedingJoinPoint, $value)) {
                return [$proceedingJoinPoint->process(), false];
            }
            return [$proceedingJoinPoint->process(), true];
        }

        $cacheKey = $this->cache->key($value, $annotation->config, $annotation->prefix);
        $expire = $this->cache->getConfig($annotation->config, 'expire');

        return $this->cache->get($cacheKey, function () use ($proceedingJoinPoint, $annotation, $value) {
            if ($annotation->dispatchLoopEnable === true
            && false === $this->dispatchLoop->asyncJobPush($annotation, $proceedingJoinPoint, $value)) {
                return [$proceedingJoinPoint->process(), false];
            }
            return [$proceedingJoinPoint->process(), true];
        }, $expire);
    }
}
