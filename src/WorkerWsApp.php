<?php

namespace ThinkWorker;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Lib\Gateway;
use think\App;

class WorkerWsApp extends App
{
	/**
	 * 连接ID
	 */
	public string $clientId;

	/**
	 * WebSocket握手时的http头数据，包含get、server等变量
	 */
	public array $requestData = [];

	/**
	 * 当客户端发来消息时(onMessage)的具体消息内容
	 */
	public mixed $message;

	/**
	 * 运行业务逻辑进程
	 * BusinessWorker收到Gateway转发来的事件及请求时会默认调用Events.php中的onConnect onMessage onClose方法处理事件及数据
	 * @var BusinessWorker|null
	 */
	public ?BusinessWorker $worker;

	public function init(array $server = []): void
	{
		// 输入过滤
		array_walk_recursive($this->message, ['ThinkWorker\Helper', 'cleanXss']);
		array_walk_recursive($this->requestData, ['ThinkWorker\Helper', 'cleanXss']);

		$_GET = $this->requestData['get'] ?? [];
		$_REQUEST = array_merge($_REQUEST, $_GET);

		$scriptFilePath = public_path() . 'index.php';
		$_SERVER = array_merge([
			'PATH_INFO' => $this->message['pathInfo'] ?? 'worker/WebSocket/index',
			'SCRIPT_FILENAME' => $scriptFilePath,
			'SCRIPT_NAME' => DIRECTORY_SEPARATOR . pathinfo($scriptFilePath, PATHINFO_BASENAME),
			'DOCUMENT_ROOT' => dirname($scriptFilePath),
			'HTTP_ACCEPT' => 'application/json, text/plain, */*',
		], $server, $this->requestData['server'] ?? []);

		$this->message['MESSAGE_TIME'] = time();
		$this->initialize();
	}

	/**
	 * 发送消息
	 * @param string $type 消息类型
	 * @param mixed $data 消息数据
	 * @param ?mixed $uid uid
	 */
	public function send(string $type, mixed $data, mixed $uid = null): bool
	{
		$result = $this->assembleMessage($type, $data);

		if (! is_null($uid)) {
			if (Gateway::isUidOnline($uid)) {
				Gateway::sendToUid($uid, $result);
			}
			
			return true;
		}

		if (Gateway::isOnline($this->clientId)) {
			return Gateway::sendToClient($this->clientId, $result);
		}

		return true;
	}

	/**
	 * 组装一条 ws 消息
	 * @param string $type 消息类型
	 * @param mixed $data 消息数据
	 */
	public function assembleMessage(string $type, mixed $data): string
	{
		return json_encode([
			'type' => $type,
			'data' => $data,
			'time' => $this->message['MESSAGE_TIME'] ?? '',
		]);
	}
}