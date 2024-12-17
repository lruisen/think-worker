<?php

namespace ThinkWorker\Handlers;

use Exception;
use think\console\Output;
use think\facade\Config;
use think\helper\Arr;
use think\queue\Listener;
use Workerman\Worker;

class QueueHandle
{
	protected ?Listener $listener = null;

	/**
	 * Queue Worker constructor.
	 * @param array $workers
	 */
	public function __construct(
		protected array $workers = [],
	)
	{
		$this->listener = app()->make(Listener::class);
		$this->listener->setOutputHandler(function ($type, $line) {
			app()->make(Output::class)->write($line);
		});
	}


	/**
	 * onWorkerStart.
	 * @param Worker $worker
	 * @return void
	 * @throws Exception
	 */
	public function onWorkerStart(Worker $worker): void
	{
		foreach ($this->workers as $queue => $options) {
			if (str_contains($queue, '@')) {
				[$queue, $connection] = explode('@', $queue);
			} else {
				$connection = Config::get('queue.default');
			}

			$delay = Arr::get($options, 'delay', 0);
			$sleep = Arr::get($options, 'sleep', 3);
			$tries = Arr::get($options, 'tries', 0);
			$memory = Arr::get($options, 'memory', 128);
			$timeout = Arr::get($options, 'timeout', 60);

			$this->listener->listen($connection, $queue, $delay, $sleep, $tries, $memory, $timeout);
		}
	}
}