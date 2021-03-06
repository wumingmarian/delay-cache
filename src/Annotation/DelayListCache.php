<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Wumingmarian\DelayCache\Job\DelayListCacheJob;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Class DelayListCache
 * @package Wumingmarian\DelayCache\Annotation
 */
class DelayListCache extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $driver;
    /**
     * @var string
     */
    public $value = 'data';
    /**
     * @var string
     */
    public $dispatchLoopEnable = true;
    /**
     * @var string
     */
    public $job = DelayListCacheJob::class;
    /**
     * @var int
     */
    public $delay;
    /**
     * @var string
     */
    public $prefix = "delay_cache";
    /**
     * @var string
     */
    public $config;
    /**
     * @var int
     */
    public $cacheLimit = 150;
    /**
     * @var string
     */
    public $pageName = "page";
    /**
     * @var string
     */
    public $pagesName = "pages";
    /**
     * @var string
     */
    public $sortByName = 'sort_by';

    public function __construct(...$value)
    {
        parent::__construct(...$value);
    }
}