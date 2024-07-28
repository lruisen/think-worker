<?php

namespace ThinkWorker;

use ThinkWorker\command\Worker;
use ThinkWorker\command\WorkerForWindows;

class Service extends \think\Service
{
    public function register(): void
    {
        defined('__WT_PKG__') or define('__WT_PKG__', __DIR__);

        $this->commands([
            'worker' => Worker::class,
            'worker:win' => WorkerForWindows::class
        ]);
    }
}