<?php

namespace ThinkWorker;

use ThinkWorker\Commands\CronWorker;
use ThinkWorker\Commands\HttpWorker;
use ThinkWorker\Commands\QueueWorker;
use ThinkWorker\Commands\Worker;

class Service extends \think\Service
{
	public function register(): void
	{
		defined('__PKG__') or define('__PKG__', __DIR__);

		$this->commands([
			'worker' => Worker::class,
			'worker:http' => HttpWorker::class,
			'worker:cron' => CronWorker::class,
			'worker:queue' => QueueWorker::class,
		]);
	}
}