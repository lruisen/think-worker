<?php

namespace ThinkWorker\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use ThinkWorker\service\HttpService;
use ThinkWorker\service\WebSocketService;
use Workerman\Worker as WorkerManWorker;

class Worker extends Command
{
	public function configure(): void
	{
		$this->setName('worker')
			->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
			->setDescription('Starting HTTP|WS Service through Workerman');
	}

	public function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));

		ini_set('display_errors', 'on');
		error_reporting(E_ALL);

		$this->checkParameters($action);
		$this->checkExtensions();

		global $argv;
		array_shift($argv);
		array_shift($argv);
		array_unshift($argv, 'Worker', $action);


		// 热更新
		$this->startMonitor();

		// 初始化WebServer服务（http服务）
		$this->initHttpService();

		// 初始化WebSocket服务
		$this->initWebSocketService();

		// 加载定时任务
		$this->initCrontab();

		// 加载其他自定义进程
		$this->startOtherProcess();

		// worker 主进程重载时重新 编译并缓存 PHP 脚本
		$this->setMasterReload();

		// 启动全部服务
		WorkerManWorker::runAll();

		$output->writeln('You can exit with <info>`CTRL-C`</info>');
	}

	/**
	 * 初始化 HttpService
	 * @return void
	 */
	protected function initHttpService(): void
	{
		if (! Config::get('worker_http.enable')) {
			return;
		}

		$worker = new HttpService();
		$worker->init();
	}

	/**
	 * 初始化 WebSocketService
	 * @return void
	 */
	protected function initWebSocketService(): void
	{
		$ws_enable = Config::get('worker_ws.enable');
		if (! $ws_enable) {
			return;
		}

		$worker = new WebSocketService();
		$worker->init();
	}

	/**
	 * 开启热更新
	 * @return void
	 */
	protected function startMonitor(): void
	{
		if (! Config::get('worker_process.monitor.constructor.switch', false)) {
			return;
		}

		$config = Config::get('worker_process.monitor');
		worker_start('monitor', $config);
	}


	/**
	 * 检查参数
	 * @param string $action 操作类型
	 * @return void
	 */
	protected function checkParameters(string $action): void
	{
		if (is_windows()) {
			$this->output->writeln("<error>Windows does not support command startup. Please run command php think worker:win to run it.</error>");
			exit(1);
		}

		if (! in_array($action, ['start', 'stop', 'restart', 'reload', 'status'])) {
			$this->output->writeln("<error>Invalid argument action:$action, Expected start|stop|restart|reload|status .</error>");
			exit(1);
		}
	}

	/**
	 * 检测扩展是否安装
	 * @return void
	 */
	protected function checkExtensions(): void
	{
		if (! extension_loaded('pcntl')) {
			$this->output->writeln("<error>Please install pcntl extension. See https://doc.workerman.net/appendices/install-extension.html </error>");
			exit(1);
		}

		if (! extension_loaded('posix')) {
			$this->output->writeln("<error>Please install posix extension. See https://doc.workerman.net/appendices/install-extension.html </error>");
			exit(1);
		}
	}

	/**
	 * 主进程收到重载信号 时编译并缓存 PHP 脚本
	 * @return void
	 */
	protected function setMasterReload(): void
	{
		WorkerManWorker::$onMasterReload = function () {
			if (! function_exists('opcache_get_status')) {
				return;
			}

			if (! $status = opcache_get_status()) {
				return;
			}

			if (isset($status['scripts']) && $scripts = $status['scripts']) {
				foreach (array_keys($scripts) as $file) {
					opcache_invalidate($file, true);
				}
			}
		};
	}

	/**
	 * 初始化其他进程
	 * @return void
	 */
	public function startOtherProcess(): void
	{
		$process = config('worker_process');
		unset($process['monitor']);

		foreach ($process as $process_name => $config) {
			if (empty($config['enable'])) {
				continue;
			}

			worker_start($process_name, $config);
		}
	}

	/**
	 * 初始化定时任务
	 * @return void
	 */
	public function initCrontab(): void
	{
		$crontab = config('worker_crontab', []);
		if (empty($crontab['enable'])) {
			return;
		}

		foreach ($crontab['processes'] as $process_name => $config) {
			worker_start($process_name, $config);
		}
	}
}