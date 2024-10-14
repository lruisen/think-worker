<?php

namespace ThinkWorker\service;

use GatewayWorker\BusinessWorker;
use think\db\exception\PDOException;
use think\facade\App;
use think\facade\Config;
use think\facade\Db;
use ThinkWorker\Monitor;

class HandleBusinessEvents
{

	/**
	 * 文件监听配置
	 */
	protected static array $monitorConfig = [];

	/**
	 * 初始 $_SERVER 数据
	 */
	protected static array $serverData;

	protected static ?\think\Db $db = null;

	protected static BusinessWorker $worker;

	/**
	 * Worker子进程启动时的回调函数，每个子进程启动时都会执行。
	 */
	public static function onWorkerStart(BusinessWorker $worker): void
	{
		self::$worker = $worker;
		if (! self::$monitorConfig) {
			self::$monitorConfig = Config::get('worker_process.monitor.constructor');
		}

		if (! self::$db) {
			try {
				Db::execute("SELECT 1");
				$app = App::getInstance();
				self::$db = $app->db;
			} catch (PDOException) {
			}
		}

		if (0 == $worker->id) {
			new Monitor(self::$monitorConfig);
		}
	}

	public static function onWorkerStop(BusinessWorker $worker): void
	{

	}

	/**
	 * WebSocket 链接成功
	 *
	 * @param string $clientId 连接id
	 * @param array $data websocket握手时的http头数据，包含get、server等变量
	 */
	public static function onWebSocketConnect(string $clientId, array $data): void
	{
		self::$serverData = $_SERVER;
		$_SESSION['requestData'] = $data;
	}

	/**
	 * 当客户端发来消息时触发
	 * @param string $clientId 连接id
	 * @param mixed $message 具体消息
	 */
	public static function onMessage(string $clientId, mixed $message): bool
	{
		if ($message == 'ping') return true;

		$app = new WorkerWsApp(root_path());
		$app->db = self::$db;
		$app->worker = self::$worker;
		$app->clientId = $clientId;
		$app->requestData = $_SESSION['requestData'] ?? [];

		$app->message = json_decode($message, true);
		if (json_last_error() != JSON_ERROR_NONE) {
			return $app->send('error', [
				'message' => 'Message parsing error:' . json_last_error_msg(),
				'code' => 500,
			]);
		}

		$app->init(self::$serverData ?? []);

		$http = $app->http;
		$response = $http->run();
		$code = $response->getCode();

		if ($code >= 300) {
			$content = $response->getContent();
			$content = json_decode($content, true);
			$content['code'] = $code;
			$app->send('error', $content);
		}

		$http->end($response);
		return true;
	}

	/**
	 * 当用户断开连接时触发
	 * @param string $clientId 连接id
	 */
	public static function onClose(string $clientId): void
	{

	}
}