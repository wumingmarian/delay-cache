<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Job;

use Wumingmarian\DelayCache\Cache;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

class DelayListCacheJob extends AbstractDelayCacheJob
{
    /**
     * @return bool
     * @throws ConfigureNotExistsException
     */
    public function handle()
    {
        $cache = make(Cache::class);
        $cacheKey = $cache->key($this->data, $this->annotation->config, $this->annotation->prefix);

        $this->data[$this->annotation->pageName] = 1;
        $this->data[$this->annotation->pagesName] = $this->annotation->cacheLimit;
        $this->data['__DISPATCH_LOOP__'] = true;
        [$res, $isCache] = make($this->class)->{$this->method}($this->data);
        if (true === $isCache) {
            $cache->setByPaginate($cacheKey,$res);
        }
        return true;
    }
}
