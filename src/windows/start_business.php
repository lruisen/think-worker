#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../../autoload.php';

use GatewayWorker\BusinessWorker;
use ThinkWorker\think\Application;
use Workerman\Worker;

Application::getInstance()->initialize();

if (config('worker_ws.enable', false)) {
	$config = config('worker_ws');

	/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
	$business = new BusinessWorker();

	// 设置 Business 参数
	foreach ($config['business'] as $key => $value) {
		$business->$key = $value;
	}

	Worker::runAll();
}