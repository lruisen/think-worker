<?php

namespace think;

require_once __DIR__ . '/../../vendor/autoload.php';

use GatewayWorker\Register;
use ThinkWorker\Application;
use Workerman\Worker;

Application::getInstance()->initialize();

$config = config('worker_ws.register.constructor', []);

// 注册(Register)服务
new Register("text://{$config['ip']}:{$config['port']}");

Worker::runAll();
