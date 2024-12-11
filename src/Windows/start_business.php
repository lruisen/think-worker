<?php

namespace think;

require_once __DIR__ . '/../../vendor/autoload.php';

use GatewayWorker\BusinessWorker;
use ThinkWorker\Application;
use Workerman\Worker;

Application::getInstance()->initialize();

$config = config('worker_ws.business.constructor', []);

/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
$business = new BusinessWorker();

// 设置 Business 参数
foreach ($config['constructor'] as $key => $value) {
	$business->$key = $value;
}

Worker::runAll();
