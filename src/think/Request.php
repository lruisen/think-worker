<?php

namespace ThinkWorker\think;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;

class Request extends \think\Request
{
	/**
	 * 当前worker请求对象
	 * @var WorkerRequest
	 */
	protected WorkerRequest $workerRequest;

	/**
	 * 获取原始请求数据
	 * @return string
	 */
	public function rawBody(): string
	{
		return $this->getInput();
	}
	
	/**
	 * 重新初始化 Request 类
	 * @access public
	 * @param Application $app
	 * @param TcpConnection $connection
	 * @param WorkerRequest $request
	 * @return void
	 */
	public function reinitialize(Application $app, TcpConnection $connection, WorkerRequest $request): void
	{
		// 保存 php://input
		$this->input = $request->rawBody();
		// 请求头
		$this->header = array_change_key_case($request->header());
		// SERVER参数
		$this->server = $_SERVER = $this->parseServer($app, $connection, $request);
		// 环境变量
		$this->env = $app->env;
		// GET参数
		$this->get = $_GET = $request->get();
		// input数据
		$inputData = $this->getInputData($this->input);
		// POST参数
		$_POST = $request->post();
		$this->post = $_POST ?: $inputData;
		// PUT参数
		$this->put = $inputData;
		// COOKIE参数
		$this->cookie = $_COOKIE = $request->cookie();
		// REQUEST参数
		$this->request = $_REQUEST = array_merge([], $_GET, $_POST, $_COOKIE);
		// FILE参数
		$this->file = $_FILES = $request->file();
		// 当前worker请求对象
		$this->workerRequest = $request;
	}

	/**
	 * 解析当前SERVER超全局变量
	 * @access protected
	 * @param Application $app
	 * @param TcpConnection $connection
	 * @param WorkerRequest $request
	 * @return array
	 */
	protected function parseServer(Application $app, TcpConnection $connection, WorkerRequest $request): array
	{
		$scriptFilePath = public_path() . 'index.php';
		$service = array_merge($_SERVER, [
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
			'SERVER_SOFTWARE' => 'WorkerMan Development Server',
		]);

		return array_merge($service, array_change_key_case($request->header(), CASE_UPPER));
	}
}