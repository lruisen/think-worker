<?php

namespace ThinkWorker\Gateway;

class BusinessWorker
{
	public function __construct(protected array $config)
	{
		$this->init();
	}

	protected function init(): void
	{
		/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
		$business = new \GatewayWorker\BusinessWorker();

		// 设置 Business 参数
		foreach ($this->config as $key => $value) {
			$business->$key = $value;
		}
	}
}