<?php

namespace ThinkWorker\think;

use think\event\RouteLoaded;

class Http extends \think\Http
{
	protected function loadMiddleware(): void
	{
		if (is_file($this->app->getBasePath() . 'middleware.php')) {
			// Change include to include_once OnlyOne
			$middleware = include_once $this->app->getBasePath() . 'middleware.php';
			if (is_array($middleware)) {
				$this->app->middleware->import($middleware);
			}
		}
	}

	protected function loadRoutes(): void
	{
		$routePath = $this->getRoutePath();

		if (is_dir($routePath)) {
			$files = glob($routePath . '*.php');
			foreach ($files as $file) {
				// Change include to include_once
				include_once $file;
			}
		}

		$this->app->event->trigger(RouteLoaded::class);
	}
}