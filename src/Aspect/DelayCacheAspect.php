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

        if ($this->isDispatchLoop($value)) {
            $this->asyncJobPush($annotation, $proceedingJoinPoint, $value);
            return $proceedingJoinPoint->process();
        }

        $cacheKey = $this->cache->key($value, $annotation->config, $annotation->prefix);

        return $this->cache->get($cacheKey, function () use ($proceedingJoinPoint, $annotation, $value) {
            $res = $proceedingJoinPoint->process();
            if ($annotation->dispatchLoopEnable === true) {
                $this->asyncJobPush($annotation, $proceedingJoinPoint, $value);
            }
            return $res;
        });
    }
}
