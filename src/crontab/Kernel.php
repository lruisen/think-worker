<?php

namespace ThinkWorker\crontab;

abstract class Kernel
{

	abstract public function setRule();

	abstract public function handle();

}