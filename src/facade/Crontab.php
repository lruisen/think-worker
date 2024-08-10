<?php

namespace ThinkWorker\facade;

use think\Facade;

/**
 * Class Crontab
 * @package ThinkWorker\facade
 * @mixin \ThinkWorker\crontab\Crontab
 * @method static \ThinkWorker\crontab\Crontab cron(string $expression)  事件频率的Cron表达式
 * @method static \ThinkWorker\crontab\Crontab everySeconds(int $n = 1)  N秒钟钟执行一次
 * @method static \ThinkWorker\crontab\Crontab everyMinutes(int $n = 1)  N 分钟执行一次
 * @method static \ThinkWorker\crontab\Crontab hourly()  每小时运行一次
 * @method static \ThinkWorker\crontab\Crontab hourlyAt(array|int $offset)  每小时以给定的偏移量运行
 * @method static \ThinkWorker\crontab\Crontab everyOddHour()  每奇数小时运行一次
 * @method static \ThinkWorker\crontab\Crontab everyTwoHours()  每两小时运行一次
 * @method static \ThinkWorker\crontab\Crontab everyThreeHours()  每三小时运行一次
 * @method static \ThinkWorker\crontab\Crontab everyFourHours()  每四小时运行一次
 * @method static \ThinkWorker\crontab\Crontab everySixHours()  每六小时举行一次
 * @method static \ThinkWorker\crontab\Crontab daily()  每天运行一次
 * @method static \ThinkWorker\crontab\Crontab dailyAt(string $time)  在每天的给定时间运行（如：10:00）。
 * @method static \ThinkWorker\crontab\Crontab at(string $time)  在给定时间执行.
 * @method static \ThinkWorker\crontab\Crontab days(array|mixed $days)  在一周中的哪几天运行.
 * @method static \ThinkWorker\crontab\Crontab weekdays()  在工作日运行.
 * @method static \ThinkWorker\crontab\Crontab weekends()  在每周末运行.
 * @method static \ThinkWorker\crontab\Crontab mondays()  在每周一运行.
 * @method static \ThinkWorker\crontab\Crontab tuesdays()  在每周二运行.
 * @method static \ThinkWorker\crontab\Crontab wednesdays()  在每周三运行.
 * @method static \ThinkWorker\crontab\Crontab thursdays()  在每周四运行.
 * @method static \ThinkWorker\crontab\Crontab fridays()  在每周五运行.
 * @method static \ThinkWorker\crontab\Crontab saturdays()  在每周六运行.
 * @method static \ThinkWorker\crontab\Crontab sundays()  在每周日运行.
 * @method static \ThinkWorker\crontab\Crontab weekly()  每周运行一次（周日0点运行）.
 * @method static \ThinkWorker\crontab\Crontab weeklyOn(mixed $dayOfWeek, string $time = '0:0')  在每周的给定第几天的时间运行.
 * @method static \ThinkWorker\crontab\Crontab monthly()  每月第一天运行一次.
 * @method static \ThinkWorker\crontab\Crontab monthlyOn(int $dayOfMonth = 1, string $time = '0:0')  在每月的给定日期和时间运行.
 *
 */
class Crontab extends Facade
{
	protected static function getFacadeClass(): string
	{
		return \ThinkWorker\crontab\Crontab::class;
	}
}