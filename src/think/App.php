<?php

namespace ThinkWorker\think;

use think\App as ThinkApp;
use think\Cache;
use think\Config;
use think\Console;
use think\Cookie;
use think\Db;
use think\Env;
use think\Event;
use think\Lang;
use think\Log;
use think\Middleware;
use think\Request;
use think\Response;
use think\Route;
use think\Session;
use think\Validate;
use think\View;

class App extends ThinkApp
{
	protected $bind = [
		'app' => ThinkApp::class,
		'cache' => Cache::class,
		'config' => Config::class,
		'console' => Console::class,
		'cookie' => Cookie::class,
		'db' => Db::class,
		'env' => Env::class,
		'event' => Event::class,
		'http' => Http::class, // Change think\Http to Http
		'lang' => Lang::class,
		'log' => Log::class,
		'middleware' => Middleware::class,
		'request' => Request::class,
		'response' => Response::class,
		'route' => Route::class,
		'session' => Session::class,
		'validate' => Validate::class,
		'view' => View::class,
		'think\DbManager' => Db::class,
		'think\LogManager' => Log::class,
		'think\CacheManager' => Cache::class,
		'Psr\Log\LoggerInterface' => Log::class,
	];
}