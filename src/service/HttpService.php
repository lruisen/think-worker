<?php

namespace ThinkWorker\service;

use think\App;
use think\facade\Config;
use think\facade\Db;
use ThinkWorker\Monitor;
use ThinkWorker\Server;
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
	 * @var App|null
	 */
	protected static ?App $app;

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

	/**
	 * 事件
	 * @var array|string[]
	 */
	protected array $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

	public function __construct(protected array $config = [])
	{
		if (empty($this->config)) {
			$this->config = Config::get('worker_http');
		}

		$listen = sprintf('%s://%s:%s', 'http', $this->config['option']['host'] ?? '0.0.0.0', $this->config['option']['port'] ?? '2356');
		$this->worker = new Worker($listen, $this->config['context'] ?? []);

		if (empty($this->config['option']['pidFile'])) {
			$this->config['option']['pidFile'] = runtime_path('worker') . 'worker_http.pid';
		}

		// 避免PID混乱
		$this->config['option']['pidFile'] .= sprintf('_%s', $this->config['option']['port']);
	}

	public function init(): static
	{
		foreach ($this->config['option'] as $key => $value) {
			if (in_array($key, ['protocol', 'ip', 'port'])) continue;

			if (in_array($key, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
				Worker::${$key} = $value;
			} else {
				$this->worker->$key = $value;
			}
		}

		foreach ($this->event as $event) {
			if (method_exists($this, $event)) {
				$this->worker->$event = [$this, $event];
			}
		}

		return $this;
	}

	public function onWorkerStart(Worker $worker): void
	{
		$this->lastMtime = time();

		self::$bind['worker'] = $worker;
		self::$serverData = $_SERVER;

		if (empty(self::$monitor_config)) {
			self::$monitor_config = Config::get('worker_process.monitor.constructor');
		}

		// 初始化DB和Cache
		if (empty(self::$bind['db'])) {
			try {
				Db::execute("SELECT 1");
				$app = App::getInstance();
				self::$bind['db'] = $app->db;
				self::$bind['cache'] = $app->cache;
			} catch (Throwable $e) {

			}
		}

		if (0 === $worker->id) {
			new Monitor(self::$monitor_config);
		}
	}

	public function onMessage(TcpConnection $connection, Request $request): void
	{
		if (! self::$app) {
			self::$app = new \ThinkWorker\think\App();
		}
		
		foreach (self::$bind as $key => $class) {
			self::$app->$key = $class;
		}

		$this->appInit && call_user_func_array($this->appInit, [self::$app]);
		$this->initRequest($connection, $request, self::$serverData);

		$path = $request->path() ?: '/';
		$file = public_path() . urldecode($path);

		if (is_file($file)) {
			$this->sendFile($connection, $request, $file);
		} else {
			$this->sendRequestToController($connection);
		}
	}

	/**
	 * 初始化请求参数
	 * @param TcpConnection $connection
	 * @param Request $request
	 * @param array $server
	 * @return void
	 */
	protected function initRequest(TcpConnection $connection, Request $request, array $server = []): void
	{
		self::$app->setRuntimePath(root_path('runtime'));

		$scriptFilePath = public_path() . 'index.php';
		$_SERVER = array_merge($server, [
			'QUERY_STRING' => $request->queryString(),
			'REQUEST_TIME' => time(),
			'REQUEST_METHOD' => $request->method(),
			'REQUEST_URI' => $request->uri(),
			'SERVER_NAME' => $request->host(true),
			'SERVER_PROTOCOL' => 'HTTP/' . $request->protocolVersion(),
			'SERVER_ADDR' => $connection->getLocalIp(),
			'SERVER_PORT' => $connection->getLocalPort(),
			'REMOTE_ADDR' => $connection->getRemoteIp(),
			'REMOTE_PORT' => $connection->getRemotePort(),
			'SCRIPT_FILENAME' => $scriptFilePath,
			'SCRIPT_NAME' => DIRECTORY_SEPARATOR . pathinfo($scriptFilePath, PATHINFO_BASENAME),
			'DOCUMENT_ROOT' => dirname($scriptFilePath),
			'PATH_INFO' => $request->path(),
		]);

		$headers = $request->header();
		foreach ($headers as $key => $item) {
			$hKey = str_replace('-', '_', $key);
			if ($hKey == 'content_type') {
				$_SERVER['CONTENT_TYPE'] = $item;
				continue;
			}

			if ($hKey == 'content_length') {
				$_SERVER['CONTENT_LENGTH'] = $item;
				continue;
			}

			$hKey = strtoupper(str_starts_with($hKey, 'HTTP_') ? $hKey : 'HTTP_' . $hKey);
			$_SERVER[$hKey] = $item;
		}

		$_GET = $request->get();
		$_POST = $request->post();
		$_FILES = $request->file();
		$_REQUEST = array_merge($_REQUEST, $_GET, $_POST);
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

		$http = self::$app->http;
		$response = $http->run();
		$content = ob_get_clean();

		ob_start();
		$response->send();
		self::$app->http->end($response);
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