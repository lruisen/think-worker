## Thinkphp Workerman工程，让您的Thinkphp应用 `常驻内存` 运行！

## 特别鸣谢

> 站在巨人的肩膀上
>
> 排名不分先后

* [Workerman](https://www.workerman.net)
* [Webman](https://www.workerman.net/webman)
* [Thinkphp](https://www.thinkphp.cn)

## 运行环境

* php >= 8.0
* thinkphp >= 8.0

## 介绍

* 相同的请求为php think run 耗时的N倍，快到起飞！
* 本模块直接依赖 workerman/gateway-worker，您可以使用它提供的方法，也可以直接使用 Workerman 4.x+ 的方法

## 功能特性

1. 文件监听热重载支持。
2. 本模块默认提供一个HTTP服务。
3. 基于Workerman，就像Webman也是基于它一样，但本模块的整合方式更加简洁（TP本身比Webman复杂）。

## 启动服务

## HTTP服务

* 服务配置文件位于`/config/worker_http.php`，默认端口为9501，所以一旦启动本服务，就不需要启动php think run了。
* `/config/worker_http.php` 配置文件中 `enable` 设置为 false 则不启动 http 服务
* 若您需要一些常驻内存运行程序的相关建议，请参考本介绍页的下半部分。

> 热更新配置在 `/config/worker_process/`中，APP_DEBUG = true 模式下自动开启热更新

```shell
 # 此命令单独启动 Http 服务 
 # 以下命令在linux中权限不足时请自行加sudo
 php think worker:http start #以调试模式启动服务
 php think worker:http start -d #以守护进程模式启动服务
 php think worker:http stop #停止服务
 php think worker:http restart #重启服务
 php think worker:http reload #柔性重启服务
 php think worker:http status #查看服务状态
```

## 队列

配置文件位于 `config/worker_process`
以下配置代替think-queue里的最后一步:监听任务并执行,无需另外起进程执行队列

```php
"queue" => [
    "enable" => false, // 是否开启队列监听并执行，true:开启，false:关闭
    "handler" => Queue::class,
    "count" => 1,  // 进程数量
    "constructor" => [
        "workers" => [
            // 键名是队列名称
            "default" => [
                "delay" => 0,  // 延迟执行时间，0为立即执行,
                "sleep" => 3,
                "tries" => 0, // 队列执行失败后的重试次数
                "timeout" => 60,  // 进程执行超时时间
            ],
        ],
    ]
],
```

> 具体配置参数请参考配置文件

```shell
 # 此命令单独启动 Queue 队列服务 
 # 以下命令在linux中权限不足时请自行加sudo
 php think worker:queue start #以调试模式启动服务
 php think worker:queue start -d #以守护进程模式启动服务
 php think worker:queue stop #停止服务
 php think worker:queue restart #重启服务
 php think worker:queue reload #柔性重启服务
 php think worker:queue status #查看服务状态
```

## 定时任务

配置文件位于 `config/worker_crontab`
> [基于workerman的定时任务程序crontab](https://www.workerman.net/doc/workerman/components/crontab.html)
>
> 具体配置参数请参考配置文件

## 支持`symfony/var-dumper`

> 中间件内容从 [top-think/think-swoole](https://github.com/top-think/think-swoole) 中摘抄而来

由于应用是通过php cli启动的，所以默认`symfony/var-dumper`会将调试信息打印在控制台, 通过配置中间件来支持将调试信息输出在网页上
如下是直接在配置在全局中间件上，也可以在路由定义的时候配置，建议是在路由定义的时候配置更加灵活

```php
// app/middleware.php

<?php
// 全局中间件定义文件
return [
    // ......
    \ThinkWorker\middleware\InteractsWithVarDumper::class,
];
```

## 常驻内存须知

### php cli下不支持的特性

1. Cookie和Session：我们也建议您无必要不使用，通常无需担心。
2. header：请使用TP的return Response()->header()方案设置响应头，如果是SSE等比较特别的，请使用特定格式输出对应所需的响应头内容。

### 对比传统PHP应用

1. 常驻内存模式载入程序文件、解析等之后，并不会销毁内存中的数据，使得类的定义、全局对象、类的静态成员 不会释放！ 便于后续重复利用。
2. 避免多次require/include相同的类或者常量的定义文件。
3. 避免使用exit、die，它们将导致子进程直接退出。
4. 事出反常，重启服务，顺风顺水也不妨刷新再看一遍。
5. 大多数情况下，代码在常驻内存的服务上能跑，那么php think run的服务上也能跑，可以相互映照。

## 常见问题

### http无法访问？

* 请首先排除端口问题，端口未开放占此类问题的80%以上，服务器面板开放端口，同时服务商的安全组、负载均衡、CDN等服务若有使用也需要同步开放对应端口。

### 创建https，wss服务？

* [创建https服务](https://www.workerman.net/doc/workerman/faq/secure-http-server.html)
* [创建wss服务](https://www.workerman.net/doc/workerman/faq/secure-websocket-server.html)

### 自定义协议/服务？

* 请先检查config/worker_*.php中的配置能否满足您的需求，然后您可以参考已有的http和ws服务创建您自己的定制化服务。

### 模块如何监听onWorkerStart等事件？

* 在模块开发中，您只需要于模块核心控制器中直接定义onWorkerStart、onWorkerStop、onWebSocketConnect、onWebSocketClose方法，
* 即可监听对应的事件，若不能满足您的需求，请参考已有的http服务或其他服务自定义您的专属服务。