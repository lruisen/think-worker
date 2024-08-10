<?php

use think\facade\Config;
use ThinkWorker\think\App;

require_once __DIR__ . '/vendor/autoload.php';

$app = App::getInstance();

$config = Config::load('./src/config/worker_crontab.php', 'worker_crontab');

dump($config);