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
	 * @param array $options
	 */
	public function __construct(
		protected array $options = [],
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

		$this->listener->listen($connection, $queue, $delay, $sleep, $tries, $memory, $timeout);

	}
}