<?php

namespace ThinkWorker\crontab;

use Exception;
use ThinkWorker\exceptions\TaskException;

class SampleTask extends BaseTask
{
	/**
	 * @inheritDoc
	 */
	public function handle(): void
	{
		echo date('Y-m-d H:i:s') . ' Test task' . PHP_EOL;

		if (random_int(1, 100) > 50) {
			throw new TaskException('该异常会记录 warning 日志');
		}

		
		if (random_int(1, 100) > 50) {
			throw new Exception('其他异常会记录 error 日志，不会抛出异常到 console');
		}

		// 正常结束无需返回
	}
}