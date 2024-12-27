<?php

namespace ThinkWorker\Handlers;

use Exception;
use think\App;
use think\console\Output;
use think\facade\Config;
use think\helper\Arr;
use think\queue\event\JobFailed;
use think\queue\event\JobProcessed;
use think\queue\event\JobProcessing;
use think\queue\Job;
use think\queue\Worker as ThinkWorker;
use Workerman\Worker;

class QueueHandle
{
	protected ?ThinkWorker $worker = null;

	protected ?App $app = null;
	protected ?Output $output = null;

	/**
	 * Queue Worker constructor.
	 * @param array $options
	 */
	public function __construct(
		protected array $options = [],
	)
	{
		$this->worker = app()->make(ThinkWorker::class);
		$this->app = app()->make(App::class);
		$this->output = app()->make(Output::class);
		$this->app->initialize();
	}


	/**
	 * onWorkerStart.
	 * @param Worker $worker
	 * @return void
	 * @throws Exception
	 */
	public function onWorkerStart(Worker $worker): void
	{
		
		$this->listenForEvents();

		[$queue, $connection] = str_contains($this->options['name'], '@')
			? explode('@', $this->options['name'])
			: [$this->options['name'], Config::get('queue.default')];

		$delay = Arr::get($this->options, 'delay', 0);
		$sleep = Arr::get($this->options, 'sleep', 3);
		$tries = Arr::get($this->options, 'tries', 0);
		$memory = Arr::get($this->options, 'memory', 128);
		$timeout = Arr::get($this->options, 'timeout', 60);

		$callback = fn() => $this->worker->runNextJob(
			$connection,
			$queue,
			$delay,
			$sleep,
			$tries
		);

		if (Arr::get($this->options, 'once', false)) {
			call_user_func($callback);
		} else {
			// Windows系统使用runNextJob循环执行，Linux系统使用daemon模式
			if (is_windows()) {
				while (true) {
					call_user_func($callback);
				}
			} else {
				$this->worker->daemon($connection, $queue, $delay, $sleep, $tries, $memory, $timeout);
			}
		}
	}

	protected function listenForEvents(): void
	{
		$this->app->event->listen(JobProcessing::class, function (JobProcessing $event) {
			$this->writeOutput($event->job, 'starting');
		});

		$this->app->event->listen(JobProcessed::class, function (JobProcessed $event) {
			$this->writeOutput($event->job, 'success');
		});

		$this->app->event->listen(JobFailed::class, function (JobFailed $event) {
			$this->writeOutput($event->job, 'failed');

			$this->logFailedJob($event);
		});
	}

	/**
	 * Write the status output for the queue worker.
	 *
	 * @param Job $job
	 * @param     $status
	 */
	protected function writeOutput(Job $job, $status): void
	{
		switch ($status) {
			case 'starting':
				$this->writeStatus($job, 'Processing', 'comment');
				break;
			case 'success':
				$this->writeStatus($job, 'Processed', 'info');
				break;
			case 'failed':
				$this->writeStatus($job, 'Failed', 'error');
				break;
		}
	}

	/**
	 * Format the status output for the queue worker.
	 *
	 * @param Job $job
	 * @param string $status
	 * @param string $type
	 * @return void
	 */
	protected function writeStatus(Job $job, string $status, string $type): void
	{
		$this->output->writeln(sprintf(
			"<{$type}>[%s][%s] %s</{$type}> %s",
			date('Y-m-d H:i:s'),
			$job->getJobId(),
			str_pad("{$status}:", 11),
			$job->getName()
		));
	}

	/**
	 * 记录失败任务
	 * @param JobFailed $event
	 */
	protected function logFailedJob(JobFailed $event): void
	{
		$this->app['queue.failer']->log(
			$event->connection,
			$event->job->getQueue(),
			$event->job->getRawBody(),
			$event->exception
		);
	}
}