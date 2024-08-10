<?php

use ThinkWorker\crontab\SampleTask;
use ThinkWorker\crontab\Schedule;

/**
 * ---------------------------------------------------------------------
 * 定时任务配置，这里使用的是基于workerman的定时任务程序crontab
 * 详情查阅文档 https://www.workerman.net/doc/workerman/components/crontab.html
 *
 * addTask 参数说明
 * 1. $name 任务名称，用于区分不同的任务
 * 2. $cron 定时任务时间格式，支持秒级别定时任务
 * 3. $task 任务类，必须继承自 ThinkWorker\crontab\BaseTask
 *
 * // 添加单个定时任务，独立进程
 * ->addTask('demo', '*\/1 * * * *', \ThinkWorker\crontab\SampleTask::class)
 *
 * // 添加多个定时任务，在同个进程中（注意会存在阻塞）
 * ->addTasks('task2', [
 *        ['*\/1 * * * *', \ThinkWorker\crontab\SampleTask::class],
 *        ['*\/1 * * * *', \ThinkWorker\crontab\SampleTask::class],
 * ])
 * ---------------------------------------------------------------------
 */

return [
	// 是否启用定时任务
	"enable" => true,
	// 定时任务进程配置
	"processes" => (new Schedule())
		->addTask('task1', '*/1 * * * *', SampleTask::class)
		->addTasks('task2', [
			['*/1 * * * *', SampleTask::class],
			['*/1 * * * *', SampleTask::class],
		])
		->buildProcesses(),
];