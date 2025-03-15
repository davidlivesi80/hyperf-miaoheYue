<?php
declare(strict_types=1);

namespace App\Constant;

/**
 * WebSocket 消息事件枚举
 *
 * @package App\Constants
 */
class PushEventConstant
{
    /**
     * 频道消息通知 - 事件名
     */
    const EVENT_TALK = 'event_talk';

    /**
     * 心跳消息通知 - 事件名
     */
    const EVENT_HEARD = 'event_heard';

    /**
     * 系统消息通知 - 事件名
     */
    const EVENT_SYSTEM = 'event_system';

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            self::EVENT_TALK          => '频道消息通知',
            self::EVENT_HEARD         => '心跳消息通知',
            self::EVENT_SYSTEM        => '系统消息通知',
        ];
    }
}
