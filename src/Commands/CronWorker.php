<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use ThinkWorker\Traits\WorkerTrait;

class CronWorker extends Command
{
	use WorkerTrait;

	protected const SERVER = 'Crontab';

	public function configure(): void
	{
		$this->setName('worker:cron')
			->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
			->setDescription('Workerman Crontab Server for ThinkPHP');
	}

	public function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));

		$this->checkArgs($action, self::SERVER);

		$this->setStaticOptions(self::SERVER);

		$crontab = $this->app->config->get('worker_cron');
		if (empty($crontab['enable'])) {
			$this->output->writeln("<error>配置enable未开启</error>");
			exit();
		}

		if (! is_windows()) {
			foreach ($crontab['processes'] as $process_name => $config) {
				worker_start($process_name, $config);
			}

			\Workerman\Worker::runAll();
		} else {
			$server = [];
			foreach ($crontab['processes'] as $process => $options) {
				$server[] = ['worker_cron.processes', $process];
			}

			$this->startWindowsWorker($server);
		}
	}
}