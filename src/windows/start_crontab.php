#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../autoload.php';

if (config('worker_http.enable', false)) {
	(new \ThinkWorker\think\App())->console->call('cron:schedule');
}