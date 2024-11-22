<?php

namespace ThinkWorker\think;

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

	/**
	 * 是否运行在命令行下
	 * @return bool
	 */
	public function runningInConsole(): bool
	{
		return false;
	}
}