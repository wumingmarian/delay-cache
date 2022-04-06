<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Job;

use Wumingmarian\DelayCache\Cache;

class DelayListCacheJob extends AbstractDelayCacheJob
{
    public function handle()
    {
        $cache = make(Cache::class);
        $cacheKey = $cache->key($this->data, $this->annotation->fieldConfig, $this->annotation->prefix);

        $this->data[$this->annotation->pageName] = 1;
        $this->data[$this->annotation->pagesName] = $this->annotation->cacheLimit;
        $this->data['__DISPATCH_LOOP__'] = true;
        $res = make($this->class)->{$this->method}($this->data);
        return $cache->setByPaginate($cacheKey,$res);

    }
}
