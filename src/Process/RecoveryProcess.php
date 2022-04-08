<?php

declare(strict_types=1);


namespace Wumingmarian\DelayCache\Process;


use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;

class RecoveryProcess extends AbstractProcess
{
    public $name = "RecoveryProcess";

    public function handle(): void
    {
        while (ProcessManager::isRunning()) {
            echo 1;
            sleep(1);
        }
    }
}