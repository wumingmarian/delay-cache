<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Wumingmarian\DelayCache\Job\DelayCacheJob;

/**
 * @Annotation
 * @Target({"METHOD"})
 * Class DelayCache
 * @package Wumingmarian\DelayCache\Annotation
 */
class DelayCache extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $driver = 'default';
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
    public $job = DelayCacheJob::class;
    /**
     * @var int
     */
    public $delay = 600;
    /**
     * @var string
     */
    public $prefix = "delay_cache";
    /**
     * @var string
     */
    public $fieldConfig;

    public function __construct(...$value)
    {
        parent::__construct(...$value);
    }
}