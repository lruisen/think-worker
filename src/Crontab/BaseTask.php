<?php

namespace ThinkWorker\Crontab;

use Closure;
use ThinkWorker\Contract\TaskExceptionInterface;
use Throwable;

abstract class BaseTask
{
	/**
	 * @var null|Closure
	 */
	protected ?Closure $eventBeforeExec = null;
	/**
	 * @var null|Closure
	 */
	protected ?Closure $eventAfterExec = null;

	final public function __construct()
	{
		if (method_exists($this, 'eventBeforeExec')) {
			$this->eventBeforeExec = Closure::fromCallable([$this, 'eventBeforeExec']);
		}

		if (method_exists($this, 'eventAfterExec')) {
			$this->eventAfterExec = Closure::fromCallable([$this, 'eventAfterExec']);
		}
	}

	/**
	 * 定时任务的执行入口
	 * @return void
	 */
	public static function taskExec(): void
	{
		$self = new static();
		if ($self->eventBeforeExec instanceof Closure) {
			call_user_func($self->eventBeforeExec, $self);
		}

		try {
			$self->handle();
		} catch (Throwable $e) {
			if ($e instanceof TaskExceptionInterface) {
				trace(sprintf('TaskException:%s，trance：%s', $e->getMessage(), $e->getDataAsString()), 'warning');
				return;
			}

			trace(sprintf('TaskException:%s', $e->getMessage()), 'error');
			return;
		}

		if ($self->eventAfterExec instanceof Closure) {
			call_user_func($self->eventAfterExec, $self);
		}
	}

	/**
	 * 执行业务逻辑
	 * @return void
	 * @throws TaskException
	 * @throws Throwable
	 */
	abstract public function handle(): void;
}