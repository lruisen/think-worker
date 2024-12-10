<?php

use ThinkWorker\Handlers\WebSocketHandle;

$register = [
	'ip' => '127.0.0.1',
	'port' => '1260',
];

return [
	// 是否启用 ws 服务
	'enable' => false,

	// Ws 服务处理器
	'handler' => WebSocketHandle::class,

	// 注册(Register)服务参数
	'register' => $register,

	// 网关(Gateway)服务参数
	'gateway' => [
		'protocol' => 'websocket', // 协议，支持 websocket text frame tcp
		'ip' => '0.0.0.0', // 监听地址
		'port' => '2828', // 监听端口
		'name' => 'WebSocketGateway', // 进程名称
		'count' => 1, // 进程数
		'lanIp' => '127.0.0.1',
		'startPort' => 1360,
		'pingInterval' => 55,
		'pingNotResponseLimit' => 1,
		'pingData' => '',
		'registerAddress' => "{$register['ip']}:{$register['port']}",
	],

	// 业务(Business)服务参数
	'business' => [
		'count' => 1,
		'name' => 'WebSocketBusiness',
		'eventHandler' => 'ThinkWorker\\Handlers\\HandleBusinessEvents',
		'registerAddress' => "{$register['ip']}:{$register['port']}",
	],

	// Worker的参数（支持所有配置项）
	'option' => [],

	// 网关(Gateway)上下文选项
	'gatewayContext' => [],
];