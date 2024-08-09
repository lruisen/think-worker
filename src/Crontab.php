<?php

namespace ThinkWorker;

use think\facade\Config;
use Workerman\Process;
use Workerman\Worker;

class Crontab
{
	public function __construct(
		protected array $tasks = [],
	)
	{
	}

	public function onWorkerStart(Worker $worker): void
	{
		date_default_timezone_set(Config::get('app.default_timezone', 'Asia/Shanghai'));

		foreach ($this->tasks as $task) {
			
		}
	}
}