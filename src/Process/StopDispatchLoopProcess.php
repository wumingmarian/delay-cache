<?php

declare(strict_types=1);


namespace Wumingmarian\DelayCache\Process;


use Hyperf\AsyncQueue\Message;
use Hyperf\Config\Config;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Wumingmarian\DelayCache\Job\AbstractDelayCacheJob;

class StopDispatchLoopProcess extends AbstractProcess
{
    public $name = "StopDispatchLoopProcess";
    /**
     * @var Redis|mixed
     */
    protected $redis;
    /**
     * @var Config|mixed
     */
    protected $config;

    /**
     * StopDispatchLoopProcess constructor.
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->redis = $container->get(Redis::class);
        $this->config = $container->get(Config::class);
    }

    public function handle(): void
    {
        while (ProcessManager::isRunning()) {
            $cursor = null;
            echo 2;
//            $this->config->get('async_queue.' . $this)
            foreach ($this->redis->zScan('async_delay_cache:ranking:delayed', $cursor) as $value => $score) {
//                var_dump($value, $score);
                /** @var Message $message */
                $message = unserialize($value);
                /** @var AbstractDelayCacheJob $job */
                $job = $message->job();
                $this->config->get('delay_cache.stop_dispatch_loop');
                var_dump($job);

                sleep(3);
            }
            echo 444;
            sleep(11);
        }
    }
}