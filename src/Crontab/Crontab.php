<?php

namespace ThinkWorker\Crontab;

class Crontab
{
	protected string $expression = '* * * * *';

	/**
	 * 事件频率的Cron表达式。
	 *
	 * @param string $expression Cron表达式
	 * @return static
	 */
	public function cron(string $expression): static
	{
		$this->expression = $expression;

		return $this;
	}

	/**
	 * N秒钟钟执行一次。
	 *
	 * @return $this
	 */
	public function everySeconds(int $n = 1): static
	{
		$segments = preg_split("/\s+/", $this->expression);

		array_unshift($segments, "*/$n");

		return $this->cron(implode(' ', $segments));
	}

	/**
	 * N 分钟执行一次。
	 *
	 * @return $this
	 */
	public function everyMinutes(int $n = 1): static
	{
		return $this->spliceIntoPosition(1, $n > 1 ? "*/$n" : '*');
	}

	/**
	 * 每小时运行一次。
	 *
	 * @return $this
	 */
	public function hourly(): static
	{
		return $this->spliceIntoPosition(1, 0);
	}

	/**
	 * 每小时以给定的偏移量运行.
	 *
	 * @param array|int $offset
	 * @return $this
	 */
	public function hourlyAt(array|int $offset): static
	{
		$offset = is_array($offset) ? implode(',', $offset) : $offset;

		return $this->spliceIntoPosition(1, $offset);
	}

	/**
	 * 每奇数小时举行一次。
	 *
	 * @return $this
	 */
	public function everyOddHour(): static
	{
		return $this->spliceIntoPosition(1, 0)->spliceIntoPosition(2, '1-23/2');
	}

	/**
	 * 每偶数个小时举行一次。
	 *
	 * @return $this
	 */
	public function everyTwoHours(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, '*/2');
	}

	/**
	 * 每三小时举行一次。
	 *
	 * @return $this
	 */
	public function everyThreeHours(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, '*/3');
	}

	/**
	 * 每四小时举行一次.
	 *
	 * @return $this
	 */
	public function everyFourHours(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, '*/4');
	}

	/**
	 * 每六小时举行一次。
	 *
	 * @return $this
	 */
	public function everySixHours(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, '*/6');
	}

	/**
	 * 每天运行一次。
	 *
	 * @return $this
	 */
	public function daily(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, 0);
	}

	/**
	 * 在每天的给定时间运行（如：10:00）。
	 *
	 * @param string $time 10:00
	 * @return $this
	 */
	public function dailyAt(string $time): static
	{
		$segments = explode(':', $time);

		return $this->spliceIntoPosition(2, (int)$segments[0])
			->spliceIntoPosition(1, count($segments) === 2 ? (int)$segments[1] : '0');
	}

	/**
	 * 在给定时间执行.
	 *
	 * @param string $time
	 * @return $this
	 */
	public function at(string $time): static
	{
		return $this->dailyAt($time);
	}

	/**
	 * 在一周中的哪几天运行.
	 *
	 * @param array|mixed $days
	 * @return $this
	 */
	public function days(mixed $days): static
	{
		$days = is_array($days) ? $days : func_get_args();

		return $this
			->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, 0)
			->spliceIntoPosition(5, implode(',', $days));
	}

	/**
	 * 在每周的工作日进行(周一到周五)。
	 *
	 * @return $this
	 */
	public function weekdays(): static
	{
		return $this->days(Carbon::MONDAY . '-' . Carbon::FRIDAY);
	}

	/**
	 * 每周日运行.
	 *
	 * @return $this
	 */
	public function weekends(): static
	{
		return $this->days(Carbon::SATURDAY . ',' . Carbon::SUNDAY);
	}

	/**
	 * 每周一运行。
	 *
	 * @return $this
	 */
	public function mondays(): static
	{
		return $this->days(Carbon::MONDAY);
	}

	/**
	 * 每周二运行
	 *
	 * @return $this
	 */
	public function tuesdays(): static
	{
		return $this->days(Carbon::TUESDAY);
	}

	/**
	 * 每周三运行
	 *
	 * @return $this
	 */
	public function wednesdays(): static
	{
		return $this->days(Carbon::WEDNESDAY);
	}

	/**
	 * 每周四运行
	 *
	 * @return $this
	 */
	public function thursdays(): static
	{
		return $this->days(Carbon::THURSDAY);
	}

	/**
	 * 每周五运行。
	 *
	 * @return $this
	 */
	public function fridays(): static
	{
		return $this->days(Carbon::FRIDAY);
	}

	/**
	 * 每周六运行。
	 *
	 * @return $this
	 */
	public function saturdays(): static
	{
		return $this->days(Carbon::SATURDAY);
	}

	/**
	 * 每周日运行。
	 *
	 * @return $this
	 */
	public function sundays(): static
	{
		return $this->days(Carbon::SUNDAY);
	}

	/**
	 * 每周运行一次。
	 *
	 * @return $this
	 */
	public function weekly(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, 0)
			->spliceIntoPosition(5, 0);
	}

	/**
	 * 在每周给定的天和时间运行一次.
	 *
	 * @param array|mixed $dayOfWeek
	 * @param string $time
	 * @return $this
	 */
	public function weeklyOn(mixed $dayOfWeek, string $time = '0:0'): static
	{
		$this->dailyAt($time);

		return $this->days($dayOfWeek);
	}

	/**
	 * 每月运行一次.
	 *
	 * @return $this
	 */
	public function monthly(): static
	{
		return $this->spliceIntoPosition(1, 0)
			->spliceIntoPosition(2, 0)
			->spliceIntoPosition(3, 1);
	}

	/**
	 * 每月在给定的日期和时间运行。
	 *
	 * @param int $dayOfMonth
	 * @param string $time
	 * @return static
	 */
	public function monthlyOn(int $dayOfMonth = 1, string $time = '0:0'): static
	{
		$this->dailyAt($time);

		return $this->spliceIntoPosition(3, $dayOfMonth);
	}

	/**
	 * 将给定值拼接到表达式的给定位置。
	 *
	 * @param int $position
	 * @param string|int $value
	 * @return static
	 */
	protected function spliceIntoPosition(int $position, string|int $value): static
	{
		$segments = preg_split("/\s+/", $this->expression);

		$segments[$position - 1] = $value;

		return $this->cron(implode(' ', $segments));
	}

	public function __toString()
	{
		return $this->expression;
	}
}