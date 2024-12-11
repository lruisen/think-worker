<?php

namespace ThinkWorker;


use Workerman\Worker;

abstract class Server
{
	/**
	 * worker 实例
	 * @var ?Worker
	 */
	protected ?Worker $worker = null;

	/**
	 * socket 可选参数，不填写表示不监听任何端口
	 * @var string
	 */
	protected string $listen = '';

	/**
	 * 协议 可选内容 tcp,udp,unix,http,websocket,text
	 * @var string
	 */
	protected string $protocol = 'http';

	/**
	 * 主机地址
	 * @var string
	 */
	protected string $host = '0.0.0.0';

	/**
	 * 监听端口
	 * @var string|int
	 */
	protected string|int $port = '2346';

	/**
	 * worker 非静态属性
	 * @var array
	 */
	protected array $options = [];

	/**
	 * socket的上下文选项
	 * @var array
	 */
	protected array $context = [];

	/**
	 * worker 事件处理函数
	 * @var array|string[]
	 */
	protected array $event = [
		'onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError',
		'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'
	];

	/**
	 * 初始化函数
	 * @return void
	 */
	protected function initialize(): void
	{
		// 实例化 Worker
		$this->worker = new Worker(
			$this->listen ?: $this->protocol . '://' . $this->host . ':' . $this->port,
			$this->context ?? []
		);
	}

	public function start(): void
	{
		Worker::runAll();
	}

	/**
	 * 获取 worker 实例
	 * @return Worker|null
	 */
	public function getWorker(): Worker|null
	{
		return $this->worker;
	}

	/**
	 * 设置 worker 实例
	 * @param Worker $worker
	 * @return $this
	 */
	public function setWorker(Worker $worker): static
	{
		$this->worker = $worker;

		return $this;
	}

	/**
	 * 设置当前类的options属性
	 * @param array $options
	 * @return $this
	 */
	public function options(array $options = []): static
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * 设置 worker 属性
	 * @return void
	 */
	public function setWorkerOptions(): void
	{
		$propertyMap = ['name', 'count', 'user', 'group', 'reloadable', 'reusePort', 'transport', 'protocol'];

		foreach ($propertyMap as $property) {
			if (isset($this->options[$property])) {
				$this->worker->$property = $this->options[$property];
			}
		}
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