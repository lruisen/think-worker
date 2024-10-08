# Thinkphp Workerman工程，让您的Thinkphp应用 `常驻内存` 运行！

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

1. 本模块默认提供一个HTTP服务和一个WebSocket服务。
2. 基于Workerman，就像Webman也是基于它一样，但本模块的整合方式更加简洁（TP本身比Webman复杂）。
3. 文件监听热重载支持。

## HTTP服务

* 服务配置文件位于`/config/worker_http.php`，默认端口为9501，所以一旦启动本服务，就不需要启动php think run了。
* `/config/worker_http.php` 配置文件中 `enable` 设置为 false 则不启动 http 服务
* 若您需要一些常驻内存运行程序的相关建议，请参考本介绍页的下半部分。

### Windows

```shell
php think worker:win start
```

> 热更新配置在 `/config/worker_process/`中，APP_DEBUG = true 模式下自动开启热更新

### Linux/Mac

* 以下命令权限不足请自行加sudo
* 执行php think worker start以调试模式启动服务
* 执行php think worker start -d以守护进程模式启动服务
* php think worker stop停止服务
* php think worker restart重启服务
* php think worker reload柔性重启服务
* php think worker status查看服务状态

## WEBSOCKET服务

* 本服务配置文件位于/config/worker_ws.php，默认端口为2828
* `/config/worker_ws.php` 配置文件中 `enable` 设置为 false 则不启动 websocket 服务
* 若您需要一些常驻内存运行程序的相关建议，请参考本介绍页的下半部分。
* ws服务的心跳保活机制，前端每50s向服务端发送一次字符串ping即可（您当然也可以自行实现不同的心跳方案）。
* 以下提供一份前台测试ws的代码

```javascript
// 连接
var websocket = new WebSocket('ws://127.0.0.1:2828');
websocket.onopen = function () {
    console.log('连接成功了');

    // 定时发送心跳以保持连接 - 50秒间隔为推荐值
    setInterval(() => {
        websocket.send('ping')
    }, 50000);

    // 发送消息
    websocket.send('{"pathInfo":"worker/WebSocketExamples/message"}')
};
websocket.onclose = function (evt) {
    console.log('连接关闭中', evt);
};
websocket.onmessage = function (evt) {
    console.log('收到消息', evt);
};
```

### Windows

```shell
php think worker:win start
```

> 热更新配置在 `/config/worker_process/`中，APP_DEBUG = true 模式下自动开启热更新

### Linux/Mac

```shell
php think worker start           #以调试模式启动服务
php think worker start -d        #以守护进程模式启动服务
php think worker stop            #停止服务
php think worker restart         #重启服务
php think worker reload          #柔性重启服务
php think worker status          #查看服务状态
```

> 注意：命令权限不足请自行加sudo

## 常驻内存须知

### php cli下不支持的特性

1. Cookie和Session：我们也建议您无必要不使用，通常无需担心。
2. move_uploaded_file：框架的文件上传类中已经兼容rename进行上传文件的移动。
3. header：请使用TP的return Response()->header()方案设置响应头，如果是SSE等比较特别的，请使用特定格式输出对应所需的响应头内容。

### 对比传统PHP应用

1. 常驻内存模式载入程序文件、解析等之后，并不会销毁内存中的数据，使得类的定义、全局对象、类的静态成员 不会释放！ 便于后续重复利用。
2. 避免多次require/include相同的类或者常量的定义文件。
3. 避免使用exit、die，它们将导致子进程直接退出。
4. 事出反常，重启服务，顺风顺水也不妨刷新再看一遍。
5. 大多数情况下，代码在常驻内存的服务上能跑，那么php think run的服务上也能跑，可以相互映照。

## 常见问题

### http无法访问，ws无法连接？

* 请首先排除端口问题，端口未开放占此类问题的80%以上，服务器面板开放端口，同时服务商的安全组、负载均衡、CDN等服务若有使用也需要同步开放对应端口。

### 创建https，wss服务？

* [创建https服务](https://www.workerman.net/doc/workerman/faq/secure-http-server.html)
* [创建wss服务](https://www.workerman.net/doc/workerman/faq/secure-websocket-server.html)

### 自定义协议/服务？

* 请先检查config/worker_*.php中的配置能否满足您的需求，然后您可以参考已有的http和ws服务创建您自己的定制化服务。

### 模块如何监听onWorkerStart等事件？

* 在模块开发中，您只需要于模块核心控制器中直接定义onWorkerStart、onWorkerStop、onWebSocketConnect、onWebSocketClose方法，
* 即可监听对应的事件（http服务不支持，仅ws），若不能满足您的需求，请参考已有的ws和http服务自定义您的专属服务。