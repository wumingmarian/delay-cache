<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Job;

use Wumingmarian\DelayCache\Cache;

class DelayCacheJob extends AbstractDelayCacheJob
{
    public function handle()
    {
        $cache = make(Cache::class);
        $cacheKey = $cache->key($this->data, $this->annotation->fieldConfig, $this->annotation->prefix);

        $this->data['__DISPATCH_LOOP__'] = true;
        $res = make($this->class)->{$this->method}($this->data);
        return $cache->set($cacheKey,$res);
    }
}
