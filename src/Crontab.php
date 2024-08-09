<?php

namespace ThinkWorker;

use ThinkWorker\think\App;
use Workerman\Worker;

class Crontab
{
	public function __construct()
	{
	}

	public function onWorkerStart(Worker $worker): void
	{
		(new App())->console->call('cron:schedule');
	}
}