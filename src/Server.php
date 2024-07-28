<?php

namespace ThinkWorker;

use Closure;
use Workerman\Worker;

abstract class Server
{

    protected ?Worker $worker;
    protected array $option = [];
    protected array $context = [];

    protected ?string $rootPath;
    protected ?string $root;
    protected ?Closure $appInit;
    protected array $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

    public function setRootPath($path): void
    {
        $this->rootPath = $path;
    }

    public function appInit(Closure $closure): void
    {
        $this->appInit = $closure;
    }

    public function setRoot($path): void
    {
        $this->root = $path;
    }

    public function setStaticOption($name, $value): void
    {
        Worker::${$name} = $value;
    }

    /**
     * 设置参数
     * @access public
     * @param array $options 参数
     * @return void
     */
    public function setOptions(array $options): void
    {
        // 设置参数
        if (! empty($options)) {
            foreach ($options as $key => $val) {
                $this->worker->$key = $val;
            }
        }
    }

    /**
     * 设置参数
     * @param string $name
     * @param $value
     * @return void
     */
    public function setOption(string $name, $value): void
    {
        $this->worker->$name = $value;
    }

    public function start(): void
    {
        Worker::runAll();
    }

    public function __set($name, $value)
    {
        $this->worker->$name = $value;
    }

    public function __call($method, $args)
    {
        call_user_func_array([$this->worker, $method], $args);
    }
}