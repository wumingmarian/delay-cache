<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Annotation\DelayCache;

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
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        /** @var DelayCache $annotation */
        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[DelayCache::class];
        $fieldData = $this->getFieldData($annotation, $proceedingJoinPoint);

        if ($this->isDispatchLoop($fieldData)) {
            $this->asyncJobPush($annotation, $proceedingJoinPoint, $fieldData);
            return $proceedingJoinPoint->process();
        }

        $cacheKey = $this->cache->key($fieldData, $annotation->fieldConfig, $annotation->prefix);

        return $this->cache->get($cacheKey, function () use ($proceedingJoinPoint, $annotation, $fieldData) {
            $res = $proceedingJoinPoint->process();
            if ($annotation->dispatchLoopEnable === true) {
                $this->asyncJobPush($annotation, $proceedingJoinPoint, $fieldData);
            }
            return $res;
        });
    }
}
