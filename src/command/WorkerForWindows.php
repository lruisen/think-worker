<?php

namespace ThinkWorker\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use ThinkWorker\Monitor;

class WorkerForWindows extends Command
{
    public function configure(): void
    {
        $this->setName('worker:win')
            ->addArgument('server', Argument::REQUIRED, "The server to start. http|ws", 'http')
            ->addArgument('action', Argument::REQUIRED, "start|stop|restart|reload|status", 'start')
            ->setDescription('Starting HTTP|WS Service on Linux System through Workerman');
    }

    public function execute(Input $input, Output $output): void
    {
        $server = trim($input->getArgument('server'));
        $action = trim($input->getArgument('action'));

        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        $this->checkParameters($server, $action);

        if ('start'!==$action) {
            $output->writeln("<error>Not Support action:{$action} on Windows.</error>");
            exit();
        }

        $output->writeln(sprintf('<info>Starting Workerman %s server...</info>', $server));

        $servers = [
            __WT_PKG__ . DIRECTORY_SEPARATOR . sprintf('windows_start_%s.php', $server)
        ];

        $runtimeProcessPath = $this->getRuntimeProcessPath();
        foreach (config('worker_process', []) as $processName => $config) {
            if ('queue'===$processName) {
                continue;
            }

            $servers[] = $this->write_process_file($runtimeProcessPath, $processName);
        }

        $resource = $this->open_processes($servers);
        $this->monitor($resource, $servers);

        $output->writeln('You can exit with <info>`CTRL-C`</info>');
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

    protected function getRuntimeProcessPath(): string
    {
        $runtimeProcessPath = runtime_path('windows');
        if (! is_dir($runtimeProcessPath)) {
            mkdir($runtimeProcessPath);
        }

        return $runtimeProcessPath;
    }

    /**
     * 监控文件变化，热更新
     * @param $resource
     * @param $servers
     * @return void
     */
    protected function monitor($resource, $servers): void
    {
        $options = config('worker_process.monitor.constructor', []);
        if (empty($options['switch'])) {
            return;
        }

        $monitor = new Monitor($options);
        while (true) {
            sleep(1);
            if ($monitor->checkAllFilesChange()) {
                $status = proc_get_status($resource);
                $pid = $status['pid'];

                shell_exec("taskkill /F /T /PID $pid");
                proc_close($resource);

                $resource = $this->open_processes($servers);
            }
        }
    }

    /**
     * 创建新的进程执行命令
     * @param $processFiles
     * @return resource|void
     */
    protected function open_processes($processFiles)
    {
        $pipes = [];
        $cmd = '"' . PHP_BINARY . '" ' . implode(' ', $processFiles);
        $descriptorSpec = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptorSpec, $pipes, null, null, ['bypass_shell' => true]);
        if (! $resource) {
            exit("Can not execute $cmd\r\n");
        }

        return $resource;
    }

    protected function write_process_file($runtimeProcessPath, $processName): string
    {
        $processParam = $processName;
        $configParam = "\$app->config->get('worker_process.$processName')";

        $fileContent = <<<EOF
<?php
namespace think;

require_once __DIR__ . '/../../vendor/autoload.php';

use Workerman\Worker;

ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (is_callable('opcache_reset')) {
    opcache_reset();
}

try{
	\$app = App()::getInstance()->initialize();
			
	worker_start('$processParam', $configParam);
	
	Worker::run();
}catch (\\Throwable \$e){
	dump(\$e);
}
EOF;
        $processFile = sprintf("%sstart_%s.php", $runtimeProcessPath, $processName);
        file_put_contents($processFile, $fileContent);
        return $processFile;
    }
}