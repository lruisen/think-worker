<?php

use ThinkWorker\Handlers\HttpWorkerHandle;

return [
	// 是否启用 http 服务，此参数只在  php think worker 命令下生效
	'enable' => true,

	// Http 服务处理器
	'handler' => HttpWorkerHandle::class,

	// Worker的参数（支持所有配置项）
	'protocol' => 'http', // 协议，支持 tcp udp unix http websocket text
	'listen' => 'http://0.0.0.0:8080', // 监听地址
	'reusePort' => true, // 端口复用
	'name' => 'HttpWorker', // Worker实例名称
	'count' => 1, // 进程数

	// socket 上下文选项，可配置SSL证书等
	'context' => [],

	// 静态服务器配置
	'staticServer' => [
		// 禁止访问的文件类型
		'deny' => ['php', 'bat', 'lock', 'ini'],
		// 要求浏览器下载而不是直接打开的文件类型（比如 pdf 文件内可能含有 xss 攻击代码）
		'attachment' => ['pdf'],
	],
];