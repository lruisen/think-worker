<?php

namespace ThinkWorker\Facade;

use think\Facade;

/**
 * Class Crontab
 * @package ThinkWorker\facade
 * @mixin \ThinkWorker\Crontab\Crontab
 * @method static \ThinkWorker\Crontab\Crontab cron(string $expression)  事件频率的Cron表达式
 * @method static \ThinkWorker\Crontab\Crontab everySeconds(int $n = 1)  N秒钟钟执行一次
 * @method static \ThinkWorker\Crontab\Crontab everyMinutes(int $n = 1)  N 分钟执行一次
 * @method static \ThinkWorker\Crontab\Crontab hourly()  每小时运行一次
 * @method static \ThinkWorker\Crontab\Crontab hourlyAt(array|int $offset)  每小时以给定的偏移量运行
 * @method static \ThinkWorker\Crontab\Crontab everyOddHour()  每奇数小时运行一次
 * @method static \ThinkWorker\Crontab\Crontab everyTwoHours()  每两小时运行一次
 * @method static \ThinkWorker\Crontab\Crontab everyThreeHours()  每三小时运行一次
 * @method static \ThinkWorker\Crontab\Crontab everyFourHours()  每四小时运行一次
 * @method static \ThinkWorker\Crontab\Crontab everySixHours()  每六小时举行一次
 * @method static \ThinkWorker\Crontab\Crontab daily()  每天运行一次
 * @method static \ThinkWorker\Crontab\Crontab dailyAt(string $time)  在每天的给定时间运行（如：10:00）。
 * @method static \ThinkWorker\Crontab\Crontab at(string $time)  在给定时间执行.
 * @method static \ThinkWorker\Crontab\Crontab days(array|mixed $days)  在一周中的哪几天运行.
 * @method static \ThinkWorker\Crontab\Crontab weekdays()  在工作日运行.
 * @method static \ThinkWorker\Crontab\Crontab weekends()  在每周末运行.
 * @method static \ThinkWorker\Crontab\Crontab mondays()  在每周一运行.
 * @method static \ThinkWorker\Crontab\Crontab tuesdays()  在每周二运行.
 * @method static \ThinkWorker\Crontab\Crontab wednesdays()  在每周三运行.
 * @method static \ThinkWorker\Crontab\Crontab thursdays()  在每周四运行.
 * @method static \ThinkWorker\Crontab\Crontab fridays()  在每周五运行.
 * @method static \ThinkWorker\Crontab\Crontab saturdays()  在每周六运行.
 * @method static \ThinkWorker\Crontab\Crontab sundays()  在每周日运行.
 * @method static \ThinkWorker\Crontab\Crontab weekly()  每周运行一次（周日0点运行）.
 * @method static \ThinkWorker\Crontab\Crontab weeklyOn(mixed $dayOfWeek, string $time = '0:0')  在每周的给定第几天的时间运行.
 * @method static \ThinkWorker\Crontab\Crontab monthly()  每月第一天运行一次.
 * @method static \ThinkWorker\Crontab\Crontab monthlyOn(int $dayOfMonth = 1, string $time = '0:0')  在每月的给定日期和时间运行.
 *
 */
class Crontab extends Facade
{
	protected static function getFacadeClass(): string
	{
		return \ThinkWorker\Crontab\Crontab::class;
	}
}