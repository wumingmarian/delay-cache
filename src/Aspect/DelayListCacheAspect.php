<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Annotation\DelayListCache;

/**
 * @Aspect
 */
class DelayListCacheAspect extends AbstractDelayCacheAspect
{
    public $annotations = [
        DelayListCache::class,
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
        /** @var DelayListCache $annotation */
        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[DelayListCache::class];
        $fieldData = $this->getFieldData($annotation, $proceedingJoinPoint);

        if ($this->isDispatchLoop($fieldData)) {
            $this->asyncJobPush($annotation, $proceedingJoinPoint, $fieldData);
            return $proceedingJoinPoint->process();
        }

        $fieldData[$annotation->pageName] = (string)(isset($fieldData[$annotation->pageName]) && is_numeric($fieldData[$annotation->pageName]) ? $fieldData[$annotation->pageName] : 1);
        $fieldData[$annotation->pagesName] = (string)(isset($fieldData[$annotation->pagesName]) && is_numeric($fieldData[$annotation->pagesName]) ? $fieldData[$annotation->pagesName] : 10);

        $cacheKey = $this->cache->key($fieldData, $annotation->fieldConfig, $annotation->prefix);

        return $this->cache->paginate($cacheKey, function () use ($proceedingJoinPoint, $annotation, $fieldData) {
            $proceedingJoinPoint->arguments['keys'][$annotation->value][$annotation->pageName] = 1;
            $proceedingJoinPoint->arguments['keys'][$annotation->value][$annotation->pagesName] = $annotation->cacheLimit;
            $res = $proceedingJoinPoint->process();
            if ($annotation->dispatchLoopEnable === true) {
                $this->asyncJobPush($annotation, $proceedingJoinPoint, $fieldData);
            }
            return $res;
        }, $fieldData[$annotation->pageName], $fieldData[$annotation->pagesName]);
    }
}
