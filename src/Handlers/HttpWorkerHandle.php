<?php

namespace ThinkWorker\Handlers;

use think\facade\Config;
use ThinkWorker\Application;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class HttpWorkerHandle
{
	/**
	 * 容器
	 * @var ?Application
	 */
	protected ?Application $app = null;

	/**
	 * 最后修改时间
	 * @var int|null
	 */
	protected ?int $lastMtime = null;

	public function onWorkerStart(Worker $worker): void
	{
		$this->app = new Application();

		$this->app->setRuntimePath(runtime_path());

		$this->app->workerman = $worker;

		$this->lastMtime = time();

		$this->app->initialize();
	}

	public function onMessage(TcpConnection $connection, Request $request): void
	{
		$this->app->beginMem = memory_get_usage();

		$this->app->beginTime = microtime(true);

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
	 * 访问静态文件
	 * @param TcpConnection $connection
	 * @param Request $request
	 * @param $file
	 * @return void
	 */
	protected function sendFile(TcpConnection $connection, Request $request, $file): void
	{
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
	}
}