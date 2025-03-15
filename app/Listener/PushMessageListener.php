<?php
declare(strict_types=1);

namespace App\Listener;

use App\Event\PushEvent;
use App\Support\Message;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\Annotation\Listener;

/**
 * Websocket 消息监听器
 *
 * @Listener
 */
class PushMessageListener implements ListenerInterface
{
    public function listen(): array
    {
        // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
        return [
            PushEvent::class,
        ];
    }

    /**
     * @param object|PushEvent $event
     */
    public function process(object $event)
    {
        // 事件触发后该监听器要执行的代码写在这里，比如该示例下的发送用户注册成功短信等
        // 直接访问 $event 属性获得事件触发时传递的参数值
        Message::publish(Message::create($event->event_name, $event->data));
    }
}
