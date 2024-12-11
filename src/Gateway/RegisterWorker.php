<?php

namespace ThinkWorker\Gateway;

use GatewayWorker\Register;

class RegisterWorker
{
	public function __construct(protected array $config)
	{
		$this->init();
	}

	protected function init(): void
	{
		// 注册(Register)服务
		new Register("text://{$this->config['ip']}:{$this->config['port']}");
	}
}