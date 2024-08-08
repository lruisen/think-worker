<?php

namespace ThinkWorker;

use Exception;
use think\helper\Arr;
use Workerman\Worker;

/**
 * Class Queue
 * @package ThinkWorker
 */
class Queue
{

	/**
	 * Queue Worker constructor.
	 * @param array $workers
	 */
	public function __construct(
		protected array $workers = []
	)
	{

	}

	/**
	 * onWorkerStart.
	 * @param Worker $worker
	 * @return void
	 * @throws Exception
	 */
	public function onWorkerStart(Worker $worker)
	{
		foreach ($this->workers as $queue => $options) {
			if (str_contains($queue, '@')) {
				[$queue, $connection] = explode('@', $queue);
			} else {
				$connection = null;
			}

			$delay = Arr::get($options, 'delay', 0);
			$sleep = Arr::get($options, 'sleep', 3);
			$tries = Arr::get($options, 'tries', 0);
			$timeout = Arr::get($options, 'timeout', 60);

			$work = app()->make(\think\queue\Worker::class);

			$work->runNextJob($connection, $queue, $delay, $sleep, $tries);
		}
	}
}