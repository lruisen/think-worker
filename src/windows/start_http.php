#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../../autoload.php';

use ThinkWorker\think\Application;
use Workerman\Worker;

Application::getInstance()->initialize();

if (config('worker_http.enable', false)) {
	worker_start('http_service', config('worker_http'));

	Worker::runAll();
}