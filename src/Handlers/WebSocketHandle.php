<?php

namespace ThinkWorker\Handlers;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use think\facade\Config;
use Workerman\Worker;

class WebSocketHandle
{
	public function __construct(protected array $config = [])
	{
		if (empty($this->config)) {
			$this->config = Config::get('worker_ws');
		}
	}

	public function onWorkerStart(): void
	{
		$this->initBusiness();

		$this->initGateway();

		$this->initRegister();
	}

	protected function initBusiness(): void
	{
		/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
		$business = new BusinessWorker();

		// 设置 Business 参数
		foreach ($this->config['business'] as $key => $value) {
			$business->$key = $value;
		}
	}

	protected function initGateway(): void
	{
		$listen = sprintf('%s://%s:%s', $this->config['gateway']['protocol'] ?? 'websocket', $this->config['gateway']['ip'] ?? '0.0.0.0', $this->config['gateway']['port'] ?? '2828');
		/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
		$gateway = new Gateway($listen, $this->config['gatewayContext'] ?? []);

		// worker 参数设定
		if (! empty($this->config['option'])) {
			foreach ($this->config['option'] as $key => $value) {
				if (in_array($key, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
					Worker::${$key} = $value;
				} else {
					$gateway->$key = $value;
				}
			}
		}

		// gateway 参数设定
		if (! empty($this->config['gateway'])) {
			foreach ($this->config['gateway'] as $key => $value) {
				if (! in_array($key, ['protocol', 'ip', 'port'])) {
					$gateway->$key = $value;
				}
			}
		}
	}

	protected function initRegister(): void
	{
		// 注册(Register)服务
		new Register("text://{$this->config['register']['ip']}:{$this->config['register']['port']}");
	}
}