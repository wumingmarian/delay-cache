<?php

declare(strict_types=1);


namespace Wumingmarian\DelayCache;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use Wumingmarian\DelayCache\Constants\SortBy;
use Wumingmarian\DelayCache\Exception\ConfigureNotExistsException;

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
    /**
     * @var int
     */
    protected $expire = 86400;

    public function __construct(ConfigInterface $config, Redis $redis)
    {
        $this->config = $config;
        $this->redis = $redis;
    }

    /**
     * @param $cacheKey
     * @param $callable
     * @param $expire
     * @return mixed
     */
    public function get($cacheKey, $callable, $expire)
    {
        if (!$this->redis->exists($cacheKey) && $callable instanceof \Closure) {
            [$res, $isCache] = $callable();
            if (true === $isCache) {
                $this->set($cacheKey, $res, $expire);
            } else {
                return $res;
            }
        }
        $res = $this->redis->get($cacheKey);
        return unserialize($res);
    }

    /**
     * @param $cacheKey
     * @param $res
     * @param $expire
     * @return bool
     */
    public function set($cacheKey, $res, $expire)
    {
        return $this->redis->set($cacheKey, serialize($res), $expire);
    }


    /**
     * @param $cacheKey
     * @param $callable
     * @param $expire
     * @param int $page
     * @param int $pages
     * @param string $sortBy
     * @return array
     */
    public function paginate($cacheKey, $callable, $expire, $page = 1, $pages = 10, $sortBy = SortBy::ASC)
    {
        $start = ($page - 1) * $pages;
        $end = ($pages * $page) - 1;

        if (!$this->redis->exists($cacheKey)) {
            [$res, $isCache] = $callable();
            if (true === $isCache) {
                $this->setByPaginate($cacheKey, $res, $expire);
            } else {
                return $res;
            }
        }
        return $this->getByPaginate($cacheKey, $start, $end, $sortBy);
    }

    /**
     * @param $cacheKey
     * @param $start
     * @param $end
     * @param $sortBy
     * @return array
     */
    public function getByPaginate($cacheKey, $start, $end, $sortBy)
    {
        if ($this->redis->type($cacheKey) === \Redis::REDIS_STRING) {
            return $this->redis->get($cacheKey);
        }

        $len = $this->redis->zCard($cacheKey);
        if (!$this->exceedMaxPage($len, $start)) {
            return [];
        }

        if ((int)$sortBy === SortBy::ASC) {
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
     * @param $expire
     * @return bool
     */
    public function setByPaginate($cacheKey, $res, $expire)
    {
        if (!is_array($res) || empty($res)) {
            return $this->set($cacheKey, $res, $expire);
        }
        if ($this->redis->type($cacheKey) === \Redis::REDIS_STRING) {
            $this->redis->del($cacheKey);
        }
        foreach ($res as $key => $value) {
            $this->redis->zRemRangeByScore($cacheKey, (string)$key, (string)$key);
            $this->redis->zAdd($cacheKey, $key, serialize($value));
        }
        $this->expire($cacheKey, $expire);
        return true;
    }

    /**
     * @param $len
     * @param $start
     * @return bool
     */
    public function exceedMaxPage($len, $start)
    {
        if ($start > $len) {
            return false;
        }
        return true;
    }

    /**
     * @param $fieldData
     * @param $config
     * @param null $prefix
     * @return string
     * @throws ConfigureNotExistsException
     */
    public function key($fieldData, $config, $prefix = null)
    {
        $fields = $this->getConfig($config, 'fields');

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

    /**
     * @param $config
     * @param null $index
     * @return mixed
     * @throws ConfigureNotExistsException
     */
    public function getConfig($config, $index = null)
    {
        $configName = 'delay_cache.' . $config;
        $config = $this->config->get($configName);
        if (null === $config) {
            throw new ConfigureNotExistsException("The config [$configName] is not defined");
        }

        if (!is_null($index)) {
            $config = $config[$index] ?? null;
        }

        return $config;
    }

    /**
     * @param $cacheKey
     * @return int
     */
    public function foreDel($cacheKey)
    {
        return $this->redis->del($cacheKey);
    }

    /**
     * @param $cacheKey
     * @param $expire
     * @return bool
     */
    public function expire($cacheKey, $expire)
    {
        if (!is_numeric($expire)) {
            $expire = $this->expire;
        }
        return $this->redis->expire($cacheKey, $expire);
    }
}