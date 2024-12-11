<?php

namespace ThinkWorker\Gateway;

use GatewayWorker\Gateway;
use Workerman\Worker;

class GatewayWorker
{
	public function __construct(protected array $config)
	{
		$this->init();
	}

	protected function init(): void
	{
		$listen = sprintf('%s://%s:%s', $this->config['protocol'] ?? 'websocket', $this->config['ip'] ?? '0.0.0.0', $this->config['port'] ?? '2828');
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
		if (! empty($this->config)) {
			foreach ($this->config as $key => $value) {
				if (! in_array($key, ['protocol', 'ip', 'port'])) {
					$gateway->$key = $value;
				}
			}
		}
	}
}