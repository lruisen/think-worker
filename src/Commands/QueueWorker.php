<?php

namespace ThinkWorker\Commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\helper\Str;
use ThinkWorker\Traits\WorkerTrait;

class QueueWorker extends Command
{
	use WorkerTrait;

	protected const SERVER = 'Queue';

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

		$this->checkArgs($action, self::SERVER);

		$this->setStaticOptions(self::SERVER);

		$config = $this->app->config->get('worker_process.queue');
		if (empty($config['enable'])) {
			$this->output->writeln("<error>配置enable未开启</error>");
			exit();
		}

		if (! is_windows()) {
			foreach ($config['workers'] as $process => $worker) {
				worker_start(sprintf('queue%s', Str::studly($process)), $worker);
			}

			\Workerman\Worker::runAll();
		} else {
			$server = [];
			foreach ($config['workers'] as $process => $worker) {
				$server[] = ['worker_process.queue.workers', $process];
			}

			if (empty($server)) {
				$output->writeln('<error>需要运行的服务进程不能为空</error>');
				exit();
			}

			$this->startWindowsWorker($server);
		}
	}
}