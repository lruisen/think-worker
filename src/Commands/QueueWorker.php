<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use ThinkWorker\Traits\WorkerTrait;

class QueueWorker extends Command
{
	use WorkerTrait;

	public function configure(): void
	{
		$this->setName('worker:queue')
			->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
			->setDescription('Workerman Queue Server for ThinkPHP');
	}

	public function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));

		$this->checkArgs($action);

		$config = $this->app->config->get('worker_process.queue');

		$this->setStaticOptions('queue');

		worker_start('queueWorker', $config);

		\Workerman\Worker::runAll();
	}


}