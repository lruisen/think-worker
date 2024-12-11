<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use ThinkWorker\Handlers\WebSocketHandle;
use ThinkWorker\Traits\WorkerTrait;

class Worker extends Command
{
	use WorkerTrait;

	public function configure(): void
	{
		$this->setName('worker')
			->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
			->setDescription('Starting services through Workerman');
	}

	public function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));

		$this->checkArgs($action);

		$this->setStaticOptions();

		$services = $this->getAllProcess();

		if (empty($services)) {
			$output->writeln('<error>There are no processes that need to be started.</error>');
			exit();
		}

		if (! is_windows()) {
			foreach ($services as $process => $configs) {
				worker_start($process, $configs);
			}

			$this->startWsWorker();
		} else {
			$this->startWindowsWorker($services);
		}


		\Workerman\Worker::runAll();
	}

	/**
	 * 开启 Ws 服务
	 * @return void
	 */
	protected function startWsWorker(): void
	{
		if (config('worker_ws.enable', false)) {
			new WebSocketHandle();
		}
	}

	/**
	 * 加载全部需要启动的进程
	 * @return array
	 */
	protected function getAllProcess(): array
	{
		$server = [];

		$httpConf = config('worker_http');
		if (! empty($httpConf['enable'])) {
			if (is_windows()) {
				$server[] = ['worker_http'];
			} else {
				$server['httpWorker'] = $httpConf;
			}
		}

		$configs = config('worker_process');
		foreach ($configs as $process => $options) {
			if (empty($options['enable'])) {
				continue;
			}

			if (is_windows()) {
				if ($process === 'monitor') {
					continue;
				}

				$server[] = ['worker_process', $process];
			} else {
				$server[$process] = $options;
			}
		}

		return $server;
	}
}