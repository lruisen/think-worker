<?php

namespace ThinkWorker;

use Closure;
use Workerman\Worker;

abstract class Server
{

	/**
	 * Worker实例
	 * @var Worker|null
	 */
	protected ?Worker $worker;

	/**
	 * 参数
	 * @var array
	 */
	protected array $option = [];

	/**
	 * 上下文
	 * @var array
	 */
	protected array $context = [];

	/**
	 * 根目录
	 * @var string|null
	 */
	protected ?string $rootPath = null;

	/**
	 * 根目录
	 * @var string|null
	 */
	protected ?string $root = null;

	/**
	 * 应用初始化
	 * @var Closure|null
	 */
	protected ?Closure $appInit = null;

	/**
	 * 事件
	 * @var array|string[]
	 */
	protected array $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

	/**
	 * 设置参数
	 * @param string $name
	 * @param $value
	 * @return void
	 */
	public function setOption(string $name, $value): void
	{
		$this->worker->$name = $value;
	}

	public function start(): void
	{
		Worker::runAll();
	}

	public function __set($name, $value)
	{
		$this->worker->$name = $value;
	}

	public function __call($method, $args)
	{
		call_user_func_array([$this->worker, $method], $args);
	}
}