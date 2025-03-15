<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Swoole\Constant;

return [
    'mode' => SWOOLE_PROCESS,
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
        [
            'name'      => 'ws',
            'type'      => Server::SERVER_WEBSOCKET,
            'host'      => '0.0.0.0',
            'port'      => 9502,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                // 自定义握手处理
                Event::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
                Event::ON_MESSAGE    => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
                Event::ON_CLOSE      => [Hyperf\WebSocketServer\Server::class, 'onClose'],
            ],
            'settings'  => [
                // 设置心跳检测
                'heartbeat_idle_time'      => 70,
                'heartbeat_check_interval' => 30,
            ]
        ],
    ],
    'settings' => [
        Constant::OPTION_ENABLE_COROUTINE => true,// 开启内置协程
        Constant::OPTION_WORKER_NUM => swoole_cpu_num() * 3 ,//设置启动的 Worker 进程数
        Constant::OPTION_PID_FILE => BASE_PATH . '/runtime/hyperf.pid',// master 进程的 PID
        Constant::OPTION_OPEN_TCP_NODELAY => true,// TCP 连接发送数据时会关闭 Nagle 合并算法，立即发往客户端连接
        Constant::OPTION_MAX_COROUTINE => 100000,//设置当前工作进程最大协程数量
        Constant::OPTION_OPEN_HTTP2_PROTOCOL => true,
        Constant::OPTION_MAX_REQUEST => 0,// 设置 worker 进程的最大任务数
        Constant::OPTION_SOCKET_BUFFER_SIZE => 2 * 1024 * 1024,//配置客户端连接的缓存区长度
        Constant::OPTION_BUFFER_OUTPUT_SIZE => 2 * 1024 * 1024,
        //静态资源目录
        'document_root' => BASE_PATH . '/storage',
        'enable_static_handler' => true,
        // Task Worker 数量，根据您的服务器配置而配置适当的数量
        'task_worker_num' => 4,
        // 因为 `Task` 主要处理无法协程化的方法，所以这里推荐设为 `false`，避免协程下出现数据混淆的情况
        'task_enable_coroutine' => false,
    ],
    'callbacks' => [
        Event::ON_WORKER_START => [Hyperf\Framework\Bootstrap\WorkerStartCallback::class, 'onWorkerStart'],
        Event::ON_PIPE_MESSAGE => [Hyperf\Framework\Bootstrap\PipeMessageCallback::class, 'onPipeMessage'],
        Event::ON_WORKER_EXIT => [Hyperf\Framework\Bootstrap\WorkerExitCallback::class, 'onWorkerExit'],
        Event::ON_TASK => [Hyperf\Framework\Bootstrap\TaskCallback::class, 'onTask'],
        Event::ON_FINISH => [Hyperf\Framework\Bootstrap\FinishCallback::class, 'onFinish'],
    ],
];
