<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use ThinkWorker\Traits\WorkerTrait;

class HttpWorker extends Command
{
	use WorkerTrait;

	protected const SERVER = 'Http';

	public function configure(): void
	{
		$this->setName('worker:http')
			->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
			->setDescription('Workerman HTTP Server for ThinkPHP');
	}

	public function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));

		$this->checkArgs($action, self::SERVER);

		$this->setStaticOptions(self::SERVER);

		$config = $this->app->config->get('worker_http');
		if (empty($config['enable'])) {
			$this->output->writeln("<error>配置enable未开启</error>");
			exit();
		}

		if (! is_windows()) {
			worker_start('httpWorker', $config);

			\Workerman\Worker::runAll();
		} else {
			$this->startWindowsWorker('worker_http');
		}
	}
}