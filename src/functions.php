<?php

if (!function_exists('cpu_count')) {
	/**
	 * 获取CPU数量
	 * @return int
	 */
	function cpu_count(): int
	{
		// Windows不支持进程数设置。
		if (DIRECTORY_SEPARATOR === '\\') {
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

if (!function_exists('worker_bind_events')) {
	/**
	 * worker 进程绑定回调属性
	 * @param Worker $worker
	 * @param mixed $class
	 * @return void
	 */
	function worker_bind_events(Worker $worker, mixed $class): void
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
			}
		}

		if (method_exists($class, 'onWorkerStart')) {
			call_user_func([$class, 'onWorkerStart'], $worker);
		}
	}
}