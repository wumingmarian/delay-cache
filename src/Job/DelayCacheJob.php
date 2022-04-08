<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Job;

use Wumingmarian\DelayCache\Cache;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

class DelayCacheJob extends AbstractDelayCacheJob
{
    /**
     * @return bool
     * @throws ConfigureNotExistsException
     */
    public function handle()
    {
        $cache = make(Cache::class);
        $cacheKey = $cache->key($this->data, $this->annotation->config, $this->annotation->prefix);

        $this->data['__DISPATCH_LOOP__'] = true;
        [$res, $isCache] = make($this->class)->{$this->method}($this->data);
        if ($isCache) {
            $cache->set($cacheKey, $res);
        }
        return true;
    }
}
