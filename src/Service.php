<?php

namespace ThinkWorker;

use ThinkWorker\Commands\HttpWorker;
use ThinkWorker\Commands\QueueWorker;
use ThinkWorker\Commands\Worker;
use ThinkWorker\Commands\WsWorker;

class Service extends \think\Service
{
	public function register(): void
	{
		defined('__PKG__') or define('__PKG__', __DIR__);

		$this->commands([
			'worker' => Worker::class,
			'worker:http' => HttpWorker::class,
			'worker:ws' => WsWorker::class,
			'worker:queue' => QueueWorker::class,
		]);
	}
}