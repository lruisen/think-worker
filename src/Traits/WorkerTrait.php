<?php

namespace ThinkWorker\Traits;

use ThinkWorker\Handlers\Monitor;
use Workerman\Worker;

trait WorkerTrait
{
	/**
	 * 设置 workerman 全局静态属性
	 * @param string $service 服务名称
	 * @return void
	 */
	protected function setStaticOptions(string $service = '1'): void
	{
		if (! is_dir(runtime_path('workerman'))) {
			mkdir(runtime_path('workerman'), 0755, true);
		}

		if ($this->input->hasOption('daemon')) {
			Worker::$daemonize = true;
			Worker::$stdoutFile = sprintf('%s.stdout_%s.log', runtime_path('workerman'), $service);
		}

		Worker::$pidFile = sprintf('%s_workerman_%s.pid', runtime_path(), $configs['port'] ?? $service); // 进程ID存储位置
		Worker::$logFile = sprintf('%s%s.log', runtime_path('workerman'), date('Y-m-d')); // 日志输出位置
	}

	/**
	 * 命令行启动参数校验
	 * @param string $action 命令行启动参数
	 * @return void
	 */
	protected function checkArgs(string $action): void
	{
		if (is_windows() && 'start' !== $action) {
			$this->output->writeln("<error>Not Support action:{$action} on Windows.</error>");
			exit();
		}

		if (! in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
			$this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
			exit();
		}

		if ('start' == $action) {
			$this->output->writeln('Starting Workerman http server...');
		}
	}

	/**
	 * 开启windows服务
	 * @param string $conf_file_name 配置文件名称
	 * @param ?string $firm 数组选项
	 * @return void
	 */
	protected function startWindowsWorker(string $conf_file_name, string $firm = null): void
	{
		$servers = [];

		$this->startMonitor($servers);

		$servers[] = $this->writeProcessFile($conf_file_name, $firm);

		$resource = $this->open_processes($servers);

		$this->windowMonitor($resource, $servers);
	}

	protected function startMonitor(array &$servers = []): void
	{
		$options = $this->app->config->get('worker_process.monitor.constructor');
		if (empty($options['switch'])) {
			return;
		}

		if (! is_windows()) {
			worker_start('monitorWorker', $options);
		} else {
			$servers[] = $this->writeProcessFile('monitor', 'worker_process');
		}
	}

	/**
	 * windows 系统文件监控 热更新
	 * @param $resource
	 * @param $servers
	 * @return void
	 */
	protected function windowMonitor($resource, $servers): void
	{
		$monitor = new Monitor(config('worker_process.monitor.constructor'));
		while (true) {
			sleep(1);
			if ($monitor->checkAllFilesChange()) {
				$status = proc_get_status($resource);
				$pid = $status['pid'];

				shell_exec("taskkill /F /T /PID $pid");
				proc_close($resource);

				$resource = $this->open_processes($servers);
			}
		}
	}

	/**
	 * 创建新的进程执行命令
	 * @param $processFiles
	 * @return resource|void
	 */
	protected function open_processes($processFiles)
	{
		$pipes = [];
		$cmd = '"' . PHP_BINARY . '" ' . implode(' ', $processFiles);
		$descriptorSpec = [STDIN, STDOUT, STDOUT];
		$resource = proc_open($cmd, $descriptorSpec, $pipes, null, null, ['bypass_shell' => true]);
		if (! $resource) {
			exit("Can not execute $cmd\r\n");
		}

		return $resource;
	}

	/**
	 * 生成进程启动文件
	 * @param string $processName 配置文件名称
	 * @param string $firm 配置文件 Key 值
	 * @return string
	 */
	protected function writeProcessFile(string $processName, string $firm = ''): string
	{
		$processParam = $processName;
		$configParam = $firm ? "config('$processName.$firm')" : "config('$processName')";

		$fileContent = <<<EOF
<?php
namespace think;

require_once __DIR__ . '/../../vendor/autoload.php';

use Workerman\\Worker;
use ThinkWorker\\Application;

ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (is_callable('opcache_reset')) {
    opcache_reset();
}

Application::getInstance()->initialize();
worker_start('$processParam', $configParam);

Worker::runAll();

EOF;

		$runtimeProcessPath = runtime_path('windows');
		if (! is_dir($runtimeProcessPath)) {
			mkdir($runtimeProcessPath, 0755, true);
		}

		$processFile = sprintf("%sstart_%s.php", $runtimeProcessPath, $processName);

		file_put_contents($processFile, $fileContent);

		return $processFile;
	}
}