#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../../autoload.php';

use ThinkWorker\service\HttpService;
use Workerman\Worker;

App::getInstance()->initialize();

if (config('worker_http.enable', false)) {
	$worker = new HttpService();
	$worker->init();

	Worker::runAll();
}