<?php

namespace ThinkWorker\Handlers;

use think\facade\Config;
use ThinkWorker\Gateway\BusinessWorker;
use ThinkWorker\Gateway\GatewayWorker;
use ThinkWorker\Gateway\RegisterWorker;

class WebSocketHandle
{
	public function __construct(protected array $config = [])
	{
		if (empty($this->config)) {
			$this->config = Config::get('worker_ws');
		}

		$this->init();
	}

	public function init(): void
	{
		new BusinessWorker($this->config);

		new GatewayWorker($this->config);

		new RegisterWorker($this->config);
	}

}