<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use ThinkWorker\Handlers\WebSocketHandle;
use ThinkWorker\Traits\WorkerTrait;

class WsWorker extends Command
{
	use WorkerTrait;

	public function configure(): void
	{
		$this->setName('worker:ws')
			->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
			->setDescription('Workerman Ws Server for ThinkPHP');
	}

	public function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));

		$this->checkArgs($action);

		if (! is_windows()) {
			new WebSocketHandle();
		} else {
			$services = $this->copyWsProcessFile();
			$this->runInWindows($services);
		}

		\Workerman\Worker::runAll();
	}

	/**
	 * 加载全部需要启动的进程
	 * @return array
	 */
	protected function getAllProcess(): array
	{
		$server = [];

		$configs = config('worker_ws');
		foreach ($configs as $process => $options) {
			if (is_windows()) {
				$server[] = ['worker_ws', $process];
			} else {
				$server[$process] = $options;
			}
		}

		return $server;
	}
}