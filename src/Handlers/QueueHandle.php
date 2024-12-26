<?php

namespace ThinkWorker\Handlers;

use Exception;
use think\facade\Config;
use think\helper\Arr;
use think\queue\Worker as ThinkWorker;
use Workerman\Worker;

class QueueHandle
{
	protected ?ThinkWorker $worker = null;

	/**
	 * Queue Worker constructor.
	 * @param array $options
	 */
	public function __construct(
		protected array $options = [],
	)
	{
		$this->worker = app()->make(ThinkWorker::class);
	}


	/**
	 * onWorkerStart.
	 * @param Worker $worker
	 * @return void
	 * @throws Exception
	 */
	public function onWorkerStart(Worker $worker): void
	{
		if (str_contains($this->options['name'], '@')) {
			[$queue, $connection] = explode('@', $this->options['name']);
		} else {
			$queue = $this->options['name'];
			$connection = Config::get('queue.default');
		}

		$delay = Arr::get($this->options, 'delay', 0);
		$sleep = Arr::get($this->options, 'sleep', 3);
		$tries = Arr::get($this->options, 'tries', 0);
		$memory = Arr::get($this->options, 'memory', 128);
		$timeout = Arr::get($this->options, 'timeout', 60);

		if (Arr::get($this->options, 'once', false)) {
			$this->worker->runNextJob($connection, $queue, $delay, $sleep, $tries);
		} else {
			$this->worker->daemon($connection, $queue, $delay, $sleep, $tries, $memory, $timeout);
		}
	}
}