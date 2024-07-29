#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../autoload.php';

use GatewayWorker\Gateway;
use Workerman\Worker;

App::getInstance()->initialize();

if (config('worker_ws.enable', false)) {
	$config = config('worker_ws');

	/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
	$gateway = new Gateway("{$config['gateway']['protocol']}://{$config['gateway']['ip']}:{$config['gateway']['port']}", $config['gatewayContext']);

	// 避免pid混乱
	$config['option']['pidFile'] .= '_' . $config['gateway']['port'];

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
	if (! empty($config['gateway'])) {
		foreach ($config['gateway'] as $key => $value) {
			if (! in_array($key, ['protocol', 'ip', 'port'])) {
				$gateway->$key = $value;
			}
		}
	}

	Worker::runAll();
}