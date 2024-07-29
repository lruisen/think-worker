#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../autoload.php';

(new App())->console->call('worker');