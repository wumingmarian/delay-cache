<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Annotation\DelayListCache;
use Wumingmarian\DelayCache\Constants\SortBy;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

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
     * @throws ConfigureNotExistsException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        /** @var DelayListCache $annotation */
        $annotation = $proceedingJoinPoint->getAnnotationMetadata()->method[DelayListCache::class];
        $value = $this->getValue($annotation, $proceedingJoinPoint);

        if ($this->dispatchLoop->isDispatchLoop($value)) {
            if (false === $this->dispatchLoop->asyncJobPush($annotation, $proceedingJoinPoint, $value)) {
                return [$proceedingJoinPoint->process(), false];
            }
            return [$proceedingJoinPoint->process(), true];
        }

        $value[$annotation->pageName] = (string)(isset($value[$annotation->pageName]) && is_numeric($value[$annotation->pageName]) ? $value[$annotation->pageName] : 1);
        $value[$annotation->pagesName] = (string)(isset($value[$annotation->pagesName]) && is_numeric($value[$annotation->pagesName]) ? $value[$annotation->pagesName] : 10);
        $value[$annotation->sortByName] = (string)(isset($value[$annotation->sortByName]) && in_array($value[$annotation->sortByName], [SortBy::ASC, SortBy::DESC])) ? $value[$annotation->sortByName] : SortBy::ASC;

        $cacheKey = $this->cache->key($value, $annotation->config, $annotation->prefix);
        $expire = $this->cache->getConfig($annotation->config, 'expire');
        $blockTimeout = $this->cache->getConfig($annotation->config, 'block_timeout');

        return $this->cache->paginate($cacheKey, function () use ($proceedingJoinPoint, $annotation, $value) {
            if (true === $annotation->dispatchLoopEnable
                && false === $this->dispatchLoop->asyncJobPush($annotation, $proceedingJoinPoint, $value)) {
                return [$proceedingJoinPoint->process(), false];
            }
            $proceedingJoinPoint->arguments['keys'][$annotation->value][$annotation->pageName] = 1;
            $proceedingJoinPoint->arguments['keys'][$annotation->value][$annotation->pagesName] = $annotation->cacheLimit;
            return [$proceedingJoinPoint->process(), true];
        }, $expire, $blockTimeout, $value[$annotation->pageName], $value[$annotation->pagesName], $value[$annotation->sortByName]);
    }
}
