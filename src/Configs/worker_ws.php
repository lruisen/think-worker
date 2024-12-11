<?php

use ThinkWorker\Gateway\BusinessWorker;
use ThinkWorker\Gateway\GatewayWorker;
use ThinkWorker\Gateway\RegisterWorker;

$register = [
	'ip' => '127.0.0.1',
	'port' => '1260',
];

return [

	'enable' => true, // 是否通过 worker 命令行运行

	// 注册(Register)服务参数
	'register' => [
		'enable' => true, // 此配置三项都需要为 true
		'handler' => RegisterWorker::class,
		'constructor' => [
			'ip' => $register['ip'],
			'port' => $register['port'],
		],
	],

	// 网关(Gateway)服务参数
	'gateway' => [
		'enable' => true,
		'handler' => GatewayWorker::class,
		'constructor' => [
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

			// 网关(Gateway)上下文选项
			'gatewayContext' => [],

			// worker 参数 包含静态属性和非静态属性设置
			'option' => []
		]
	],

	// 业务(Business)服务参数
	'business' => [
		'enable' => true,
		'handler' => BusinessWorker::class,
		'constructor' => [
			'count' => 1,
			'name' => 'WebSocketBusiness',
			'eventHandler' => 'ThinkWorker\\Handlers\\BusinessEventsHandle',
			'registerAddress' => "{$register['ip']}:{$register['port']}",
		]
	],

];