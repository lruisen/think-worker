<?php

namespace think;

require_once __DIR__ . '/../../vendor/autoload.php';

use GatewayWorker\Gateway;
use ThinkWorker\Application;
use Workerman\Worker;

Application::getInstance()->initialize();

$config = config('worker_ws.gateway.constructor', []);

/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
$gateway = new Gateway("{$config['protocol']}://{$config['ip']}:{$config['port']}", $config['gatewayContext']);

// 避免pid混乱
if (! empty($config['option']['pidFile'])) {
	$config['option']['pidFile'] .= '_' . $config['port'];
}

// worker 参数设定
if (! empty($config['option'])) {
	foreach ($config['option'] as $key => $value) {
		if (in_array($key, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
			Worker::${$key} = $value;
		} else {
			$gateway->$key = $value;
		}
	}
}

// gateway 参数设定
if (! empty($config)) {
	foreach ($config as $key => $value) {
		if (! in_array($key, ['protocol', 'ip', 'port'])) {
			$gateway->$key = $value;
		}
	}
}

Worker::runAll();
