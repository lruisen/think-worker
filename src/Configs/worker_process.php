<?php

use ThinkWorker\Handlers\Monitor;
use ThinkWorker\Handlers\QueueHandle;

return [

	/**
	 * ---------------------------------------------------------------------
	 * 监控进程配置
	 * 自动监控进程的内容占用比例，到达阈值时自动执行进程reload平滑重启
	 * 以及监控文件变化，以达到热更新的目的
	 * ---------------------------------------------------------------------
	 */
	'monitor' => [
		'handler' => Monitor::class,
		'constructor' => [ // 此参数将传递给 handler 的构造函数
			'options' => [
				'switch' => env('APP_DEBUG', false), // 是否开启文件监控
				'interval' => 1, // 文件监控检测时间间隔（秒）
				'soft_reboot' => true, // 在没有请求时（空闲）时才检测，仅 http 服务下有效
				// 文件监控目录
				'paths' => [
					app_path(),
					config_path(),
					root_path('route'),
					root_path('vendor/composer'),
				],
				'extensions' => ['php', 'env'], // 监控的文件类型

				/**
				 * 以下为内存监控配置（仅 Linux 系统，Win 和 Mac 均不支持）
				 * 当达到 memory_limit 时，进程将自动重启以避免内存泄露
				 * 若需手动配置以下的 memory_limit，请确保其值小于 ini_get('memory_limit')，并留有一定余地，以避免 Allowed memory size of XXX bytes exhausted
				 */
				// 'memory_limit' => '102M', // 默认取值为 ini_get('memory_limit') 的 80%，你也可以手动配置，单位可以为：G、M、K
				'memory_monitor_interval' => 60, // 内存检测时间间隔（秒）
			]
		]
	],

	/**
	 * ---------------------------------------------------------------------
	 * think-queue队列支持
	 * 代替think-queue里的最后一步:监听任务并执行,无需另外起进程执行队列
	 * 如果enable属性设置为false时，需自行执行think-queue的监听已经守护进程执行
	 *
	 *  workers 参数说明，此处参数等效于think-queue的命令行参数
	 *  php think queue:work --queue=default --once=1 --delay=3 --sleep=3 --tries=0 --timeout=60 --memory=128
	 *
	 *  name 等效 --queue=default
	 *  delay 等效 --delay=3
	 *  sleep 等效 --sleep=3
	 *  tries 等效 --tries=0
	 *  timeout 等效 --timeout=60
	 *  memory 等效 --memory=128
	 *  once 等效 --once=1
	 *
	 *  如果需要指定驱动，可以使用 'default@connection' 的形式 例如 'default@redis'
	 * ---------------------------------------------------------------------
	 */
	'queue' => [
		'enable' => false, // 是否开启队列监听并执行，true:开启，false:关闭
		'workers' => [
			// 在windows系统靠此处键值开启进程，此处键值作为进程名称
			'default_queue' => [
				'count' => 1, // 监听队列任务的进程数
				'handler' => QueueHandle::class,
				'constructor' => [
					'options' => [
						'name' => 'default',
						'delay' => 0,
						'sleep' => 3,
						'tries' => 0,
						'timeout' => 60,
						'once' => false,
					]
				]
			],
		],
	],


	/**
	 * ---------------------------------------------------------------------
	 *
	 * 自定义进程配置
	 *
	 *  'demo' => [  // demo 为进程标识
	 *         'enable' => true,        // 是否开启进程，true:开启，false:关闭
	 *         'handler' => Demo::class,  // 此处填写进程处理类，比如 定时任务 自动执行
	 *         'listen' => 'tcp://0.0.0.0:1234', // 监听地址，协议支持：tcp udp unix http websocket text
	 *         'context' => [], // 上下文配置项，具体请参考workerman文档
	 *         'constructor' => [],         // 此处填写 handler 实例的 __constructor 函数接收的全部参数 实例化时会执行 解包
	 *         'count' => cpu_count(), // 设置当前Worker实例启动多少个进程，不设置时默认为1。
	 *         'user' => '',  // 设置当前Worker实例以哪个用户运行。此属性只有当前用户为root时才能生效。不设置时默认以当前用户运行。
	 *         'reusePort' => false, // 设置当前worker是否开启监听端口复用(socket的SO_REUSEPORT选项)。
	 *         'transport' => '', // 设置当前Worker实例所使用的传输层协议，目前只支持3种(tcp、udp、ssl)。不设置默认为tcp。
	 *         'protocol' => '', // 设置当前Worker实例的协议类。
	 *  ]
	 *
	 * ---------------------------------------------------------------------
	 */
];