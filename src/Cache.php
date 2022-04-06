<?php

declare(strict_types=1);


namespace Wumingmarian\DelayCache;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;

class Cache
{
    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var Redis
     */
    protected $redis;

    public function __construct(ConfigInterface $config, Redis $redis)
    {
        $this->config = $config;
        $this->redis = $redis;
    }

    /**
     * @param $cacheKey
     * @param null $callable
     * @return mixed
     */
    public function get($cacheKey, $callable = null)
    {
        if (!$this->redis->exists($cacheKey) && $callable instanceof \Closure) {
            $this->set($cacheKey, $callable());
        }
        $res = $this->redis->get($cacheKey) ?? [];
        return unserialize($res);
    }

    /**
     * @param $cacheKey
     * @param $res
     * @param int $expire
     * @return bool
     */
    public function set($cacheKey, $res, $expire = 604800)
    {
        return $this->redis->set($cacheKey, serialize($res), $expire);
    }


    /**
     * @param $cacheKey
     * @param $callable
     * @param int $page
     * @param int $pages
     * @param string $sortBy
     * @return array
     */
    public function paginate($cacheKey, $callable, $page = 1, $pages = 10, $sortBy = 'ASC')
    {
        $start = ($page - 1) * $pages;
        $end = ($pages * $page) - 1;

        if (!$this->redis->exists($cacheKey)) {
            $res = $callable();
            $this->setByPaginate($cacheKey, $res);
        } else {
            $len = $this->redis->zCard($cacheKey);
            if (!$this->exceedMaxPage($len, $page, $pages)) {
                $res = [];
            } else {
                $res = $this->getByPaginate($cacheKey, $start, $end, $sortBy);
            }
        }

        return $res;
    }

    /**
     * @param $cacheKey
     * @param $start
     * @param $end
     * @param string $sortBy
     * @return array
     */
    public function getByPaginate($cacheKey, $start, $end, $sortBy = 'ASC')
    {
        if ($this->redis->type($cacheKey) === \Redis::REDIS_STRING) {
            return $this->get($cacheKey);
        }

        if ($sortBy === 'ASC') {
            $res = $this->redis->zRange($cacheKey, $start, $end);
        } else {
            $res = $this->redis->zRevRange($cacheKey, $start, $end);
        }

        $res = array_map(function ($value) {
            return unserialize($value);
        }, $res);
        return $res;
    }

    /**
     * @param $cacheKey
     * @param $res
     * @return bool
     */
    public function setByPaginate($cacheKey, $res)
    {
        if (!is_array($res) || empty($res)) {
            return $this->set($cacheKey, $res);
        }
        if ($this->redis->type($cacheKey) === \Redis::REDIS_STRING) {
            $this->redis->del($cacheKey);
        }
        foreach ($res as $key => $value) {
            $this->redis->zRemRangeByScore($cacheKey, (string)$key, (string)$key);
            $this->redis->zAdd($cacheKey, $key, serialize($value));
        }
        return true;
    }

    /**
     * @param $len
     * @param $page
     * @param $pages
     * @return bool
     */
    public function exceedMaxPage($len, $page, $pages)
    {
        if ((($page - 1) * $pages) > $len) {
            return false;
        }
        return true;
    }

    /**
     * @param $fieldData
     * @param $field
     * @param null $prefix
     * @return string
     */
    public function key($fieldData, $field, $prefix = null)
    {
        $fields = $this->config->get('delay_cache.fields.' . $field);

        if (is_array($fields) && count($fields) > 1) {
            foreach ($fields as $field) {
                $cacheKeyFields[$field] = isset($fieldData[$field]) && $fieldData[$field] ? $fieldData[$field] : 'default';
            }
        }

        if (isset($cacheKeyFields) && $cacheKeyFields) {
            $fieldData = $cacheKeyFields;
        }

        if (is_array($fieldData)) {
            ksort($fieldData);
            $fieldData = join('', $fieldData);
        }

        $key = md5($fieldData);

        if ($prefix) {
            $key = $prefix . ":" . $key;
        }

        return $key;
    }
}