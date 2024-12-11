<?php

use think\Container;
use Workerman\Worker;

if (! function_exists('cpu_count')) {
	/**
	 * 获取CPU数量
	 * @return int
	 */
	function cpu_count(): int
	{
		// Windows不支持进程数设置。
		if (is_windows()) {
			return 1;
		}

		$count = 4;
		if (is_callable('shell_exec')) {
			$shell = strtolower(PHP_OS) === 'darwin' ? 'sysctl -n machdep.cpu.core_count' : 'nproc';
			$count = (int)shell_exec($shell);
		}

		return $count > 0 ? $count : 4;
	}
}


if (! function_exists('worker_start')) {
	/**
	 * 开启 worker 进程
	 * @param string $process 进程名称
	 * @param array $config 进程配置
	 * @return void
	 */
	function worker_start(string $process, array $config): void
	{
		/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
		$worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);

		$propertyMap = ['count', 'user', 'group', 'reloadable', 'reusePort', 'transport', 'protocol'];
		$worker->name = $process;
		foreach ($propertyMap as $property) {
			if (isset($config[$property])) {
				$worker->$property = $config[$property];
			}
		}

		$worker->onWorkerStart = function ($worker) use ($config) {
			if (empty($config['handler'])) {
				dump("process error: the attribute handler does not exist in config\r\n");
				return;
			}

			if (! class_exists($config['handler'])) {
				dump("process error: class {$config['handler']} not exists\r\n");
				return;
			}

			$vars = empty($config['constructor']) ? [] : [$config['constructor']];
			$instance = Container::getInstance()->make($config['handler'], $vars);
			worker_bind_events($worker, $instance, $config);
		};
	}
}

if (! function_exists('worker_bind_events')) {
	/**
	 * worker 进程绑定回调属性
	 * @param Worker $worker
	 * @param mixed $class
	 * @param array $config
	 * @return void
	 */
	function worker_bind_events(Worker $worker, mixed $class, array $config = []): void
	{
		$callbackMap = [
			'onConnect',
			'onMessage',
			'onClose',
			'onError',
			'onBufferFull',
			'onBufferDrain',
			'onWorkerStop',
			'onWebSocketConnect',
			'onWorkerReload'
		];

		foreach ($callbackMap as $name) {
			if (method_exists($class, $name)) {
				$worker->$name = [$class, $name];
			} else if (! empty($config[$name]) && $config[$name] instanceof Closure) {
				$worker->$name = $config[$name];
			}
		}

		if (method_exists($class, 'onWorkerStart')) {
			call_user_func([$class, 'onWorkerStart'], $worker);
		}
	}
}

if (! function_exists('is_windows')) {
	/**
	 * 判断是否为 Windows系统
	 * @return bool
	 */
	function is_windows(): bool
	{
		return str_starts_with(strtolower(PHP_OS), 'win') || DIRECTORY_SEPARATOR === '\\';
	}
}