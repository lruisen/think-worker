<?php

namespace ThinkWorker\service;

use think\facade\Config;
use think\facade\Db;
use ThinkWorker\Monitor;
use ThinkWorker\Server;
use ThinkWorker\think\Application;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Timer;
use Workerman\Worker;

class HttpService extends Server
{
	/**
	 * 容器
	 * @var Application
	 */
	protected Application $app;

	/**
	 * 监控
	 * @var mixed
	 */
	protected mixed $monitor;

	/**
	 * 最后修改时间
	 * @var int|null
	 */
	protected ?int $lastMtime;

	/**
	 * 监控配置
	 * @var array
	 */
	protected static array $monitor_config = [];

	/**
	 * 绑定
	 * @var array
	 */
	protected static array $bind = [];

	/**
	 * 服务端数据
	 * @var array
	 */
	protected static array $serverData = [];

	/**
	 * 等待响应的请求计数
	 */
	protected static int $waitResponseCount = 0;

	public function onWorkerStart(Worker $worker): void
	{
		self::$bind['worker'] = $worker;
		self::$serverData = $_SERVER;

		$this->app = new Application();
		$this->app->setRuntimePath(root_path('runtime'));

		$this->lastMtime = time();

		$this->app->workerman = $worker;

		$this->appInit && call_user_func_array($this->appInit, [$this->app]);

		$this->app->initialize();

		if (empty(self::$monitor_config)) {
			self::$monitor_config = Config::get('worker_process.monitor.constructor');
		}

		// 初始化DB和Cache
		if (empty(self::$bind['db'])) {
			try {
				Db::execute("SELECT 1");
				self::$bind['db'] = $this->app->db;
				self::$bind['cache'] = $this->app->cache;
			} catch (Throwable $e) {

			}
		}

		if (0 === $worker->id) {
			new Monitor(self::$monitor_config);
		}
	}

	public function onMessage(TcpConnection $connection, Request $request): void
	{
		foreach (self::$bind as $key => $class) {
			$this->app->$key = $class;
		}

		$this->app->beginTime = microtime(true);
		$this->app->beginMem = memory_get_usage();
		$this->app->request->reinitialize($this->app, $connection, $request);

		$path = $request->path() ?: '/';
		$file = public_path() . urldecode($path);

		if (is_file($file)) {
			$this->sendFile($connection, $request, $file);
		} else {
			$this->sendRequestToController($connection);
		}
	}

	/**
	 * 启动
	 * @access public
	 * @return void
	 */
	public function start(): void
	{
		Worker::runAll();
	}

	/**
	 * 停止
	 * @access public
	 * @return void
	 */
	public function stop(): void
	{
		Worker::stopAll();
	}

	protected function sendFile(TcpConnection $connection, Request $request, $file): void
	{
		// 访问静态文件

		// 文件未修改，且存在 if-modified-since 则返回 304
		if (! empty($ifModifiedSince = $request->header('if-modified-since'))) {
			$modifiedTime = date('D, d M Y H:i:s', filemtime($file)) . ' ' . date_default_timezone_get();
			if ($modifiedTime === $ifModifiedSince) {
				$connection->send(new Response(304));
				return;
			}
		}

		$pathInfo = pathinfo($file);
		$response = (new Response())->withFile($file);

		// 已经检查过文件存在，无需担心后缀识别上的 /.（无后缀） 攻击
		if (! empty($pathInfo['extension'])) {
			$extension = strtolower($pathInfo['extension']);

			// 禁止访问的文件
			if (in_array($extension, Config::get('worker_http.staticServer.deny'))) {
				$connection->send(new Response(404));
				return;
			}

			// 要求浏览器下载而不是预览
			if (in_array($extension, Config::get('worker_http.staticServer.attachment'))) {
				$response->withHeader('Content-Disposition', "attachment; filename={$pathInfo['basename']}");
			}
		}

		// 文件修改过或没有 if-modified-since 头则发送文件
		$connection->send($response);
	}

	protected function sendRequestToController(TcpConnection $connection): void
	{
		self::$waitResponseCount++;
		if (self::$monitor_config['soft_reboot'] && ! Monitor::isPaused()) {
			Monitor::pause();
		}

		// 避免输出到命令行窗口
		while (ob_get_level() > 1) {
			ob_end_clean();
		}

		ob_start();

		$http = $this->app->http;
		$response = $http->run();
		$content = ob_get_clean();

		ob_start();
		$response->send();
		$this->app->http->end($response);
		$content .= ob_get_clean() ?: '';

		$connection->send(new Response($response->getCode(), $response->getHeader(), $content));

		self::$waitResponseCount--;
		if (self::$waitResponseCount <= 0 && self::$monitor_config['soft_reboot']) {
			// 隔一次时间间隔再启动检测
			Timer::add(self::$monitor_config['interval'], function () {
				Monitor::isPaused() && Monitor::resume();
			}, [], false);
		}
	}
}