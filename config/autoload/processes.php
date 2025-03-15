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
return [
    //异步消费进程
    Hyperf\AsyncQueue\Process\ConsumerProcess::class,//异步消息
    Hyperf\Crontab\Process\CrontabDispatcherProcess::class,//定时任务
];
