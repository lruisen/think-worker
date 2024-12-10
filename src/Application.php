<?php

namespace ThinkWorker;

use think\App;
use Workerman\Worker;

/**
 * @property Http $http
 * @property Request $request
 */
class Application extends App
{
	public ?Worker $workerman = null;

	public function __construct(string $rootPath = '')
	{
		$this->bind = array_merge($this->bind, [
			'http' => Http::class,
			'request' => Request::class,
		]);

		parent::__construct($rootPath);
	}

	public function runningInConsole(): bool
	{
		return false;
	}

}