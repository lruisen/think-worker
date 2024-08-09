#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../../autoload.php';

use GatewayWorker\Register;
use Workerman\Worker;

App::getInstance()->initialize();

if (config('worker_ws.enable', false)) {
	$config = config('worker_ws');

	// 注册(Register)服务
	new Register("text://{$config['register']['ip']}:{$config['register']['port']}");

	Worker::runAll();
}