<?php

use ThinkWorker\crontab\Schedule;

/**
 * ---------------------------------------------------------------------
 * 定时任务配置，这里使用的是基于workerman的定时任务程序crontab
 * 详情查阅文档 https://www.workerman.net/doc/workerman/components/crontab.html
 *
 * addTask 参数说明
 * 1. $name 任务名称，用于区分不同的任务
 * 2. $cron 定时任务时间格式，支持秒级别定时任务，具体请查看下方时间说明
 * 3. $task 任务类，必须继承父类 ThinkWorker\crontab\BaseTask
 *
 * 添加单个定时任务，独立进程
 * ->addTask('demo', \ThinkWorker\facade\Crontab::cron("* * * * * *"), \ThinkWorker\crontab\SampleTask::class)
 *
 * 添加多个定时任务，在同个进程中（注意会存在阻塞）
 * ->addTasks('task2', [
 *        [\ThinkWorker\facade\Crontab::hourly(), \ThinkWorker\crontab\SampleTask::class],
 *        [\ThinkWorker\facade\Crontab::daily(), \ThinkWorker\crontab\SampleTask::class],
 * ])
 *
 *  时间说明
 *  *   *   *   *   *   *
 *  0   1   2   3   4   5
 *  |   |   |   |   |   |
 *  |   |   |   |   |   +------ day of week (0 - 6) (Sunday=0)
 *  |   |   |   |   +------ month (1 - 12)
 *  |   |   |   +-------- day of month (1 - 31)
 *  |   |   +---------- hour (0 - 23)
 *  |   +------------ min (0 - 59)
 *  +-------------- sec (0-59)[可省略，如果没有0位,则最小时间粒度是分钟]
 * ---------------------------------------------------------------------
 */

return [
	// 是否启用定时任务
	"enable" => false,
	// 定时任务进程配置
	"processes" => (new Schedule())
		// 在此处使用addTask或addTasks添加任务
		->buildProcesses(),
];