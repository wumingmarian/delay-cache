<?php

declare(strict_types=1);

namespace Wumingmarian\DelayCache\Job;

use Hyperf\AsyncQueue\Job;
use Wumingmarian\DelayCache\Annotation\DelayCache;
use Wumingmarian\DelayCache\Annotation\DelayListCache;

abstract class AbstractDelayCacheJob extends Job
{
    /**
     * @var mixed
     */
    public $data;
    /**
     * 任务执行失败后的重试次数，即最大执行次数为 $maxAttempts+1 次
     *
     * @var int
     */
    protected $maxAttempts = 2;
    /**
     * @var string
     */
    protected $class;
    /**
     * @var string
     */
    protected $method;

    /**
     * @var DelayCache|DelayListCache
     */
    protected $annotation;

    public function __construct($class, $method, $annotation, $data, $maxAttempts = 2)
    {
        $this->class = $class;
        $this->method = $method;
        $this->annotation = $annotation;
        $this->data = $data;
        $this->maxAttempts = $maxAttempts;
    }
}