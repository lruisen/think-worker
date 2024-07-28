<?php

namespace ThinkWorker\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use ThinkWorker\service\HttpService;
use ThinkWorker\service\WebSocketService;
use Workerman\Worker as WorkerManWorker;

class Worker extends Command
{
    public function configure(): void
    {
        $this->setName('worker')
            ->addArgument('server', Argument::REQUIRED, "The server to start. http|ws", 'http')
            ->addArgument('action', Argument::REQUIRED, "start|stop|restart|reload|status", 'start')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('Starting HTTP|WS Service on Linux System through Workerman');
    }

    public function execute(Input $input, Output $output): void
    {
        $server = trim($input->getArgument('server'));
        $action = trim($input->getArgument('action'));

        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        $this->checkParameters($server, $action);
        $this->checkExtensions();

        if (! is_windows()) {
            global $argv;
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'Worker', $action);
        } elseif ('start'!==$action) {
            $output->writeln("<error>Not Support action:{$action} on Windows.</error>");
            exit();
        }

        if ('start'==$action) {
            $output->writeln(sprintf('<info>Starting Workerman %s server...</info>', $server));
        }

        $worker = 'http'===$server ? $this->initHttpService() : $this->initWebSocketService();

        // 热更新
        if (Config::get('worker_process.monitor.constructor.switch', false)) {
            $this->startMonitor();
        }

        // 加载其他自定义进程
        $this->startOtherProcess();

        // worker 主进程重载时重新 编译并缓存 PHP 脚本
        $this->setMasterReload();

        // 启动服务
        $worker->start();

        $output->writeln('You can exit with <info>`CTRL-C`</info>');
    }

    /**
     * 初始化 HttpService
     * @return HttpService
     */
    protected function initHttpService(): HttpService
    {
        return new HttpService();
    }

    /**
     * 初始化 WebSocketService
     * @return WebSocketService
     */
    protected function initWebSocketService(): WebSocketService
    {
        return new WebSocketService();
    }

    /**
     * 开启热更新
     * @return void
     */
    protected function startMonitor(): void
    {
        if (is_windows()) {
            return;
        }

        $config = Config::get('worker_process.monitor');
        worker_start('monitor', $config);
    }


    /**
     * 检查参数
     * @param string $server 服务类型
     * @param string $action 操作类型
     * @return void
     */
    protected function checkParameters(string $server, string $action): void
    {
        if (! in_array($server, ['http', 'ws'])) {
            $this->output->writeln("<error>Invalid argument server:$server, Expected http|ws .</error>");
            exit(1);
        }

        if (! in_array($action, ['start', 'stop', 'restart', 'reload', 'status'])) {
            $this->output->writeln("<error>Invalid argument action:$action, Expected start|stop|restart|reload|status .</error>");
            exit(1);
        }
    }

    /**
     * 检测扩展是否安装
     * @return void
     */
    protected function checkExtensions(): void
    {
        // Windows 系统跳转检查
        if (is_windows()) {
            return;
        }

        if (! extension_loaded('pcntl')) {
            $this->output->writeln("<error>Please install pcntl extension. See https://doc.workerman.net/appendices/install-extension.html </error>");
            exit(1);
        }

        if (! extension_loaded('posix')) {
            $this->output->writeln("<error>Please install posix extension. See https://doc.workerman.net/appendices/install-extension.html </error>");
            exit(1);
        }
    }

    /**
     * 主进程收到重载信号 时编译并缓存 PHP 脚本
     * @return void
     */
    protected function setMasterReload(): void
    {
        WorkerManWorker::$onMasterReload = function () {
            if (! function_exists('opcache_get_status')) {
                return;
            }

            if (! $status = opcache_get_status()) {
                return;
            }

            if (isset($status['scripts']) && $scripts = $status['scripts']) {
                foreach (array_keys($scripts) as $file) {
                    opcache_invalidate($file, true);
                }
            }
        };
    }

    /**
     * 初始化其他进程
     * @return void
     */
    public function startOtherProcess(): void
    {
        if (is_windows()) {
            return;
        }

        foreach (config('worker_process') as $process_name => $config) {
            if (in_array($process_name, ['queue', 'monitor'])) {
                continue;
            }

            worker_start($process_name, $config);
        }
    }
}