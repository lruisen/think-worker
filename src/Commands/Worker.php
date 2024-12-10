<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
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

		foreach ($services as $process => $configs) {
			worker_start($process, $configs);
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

		$httpConf = config('worker_http');
		if (! empty($httpConf['enable'])) {
			$server[] = ['httpWorker' => $httpConf];
		}

		$wsConf = config('worker_ws');
		if (! empty($wsConf['enable'])) {
			$server[] = ['wsWorker' => $wsConf];
		}

		$configs = config('worker_process');
		foreach ($configs as $process => $options) {
			if (empty($options['enable'])) {
				continue;
			}

			$server[] = [$process => $options];
		}

		return $server;
	}
}