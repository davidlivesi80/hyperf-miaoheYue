<?php


namespace App\Constant;


class PushModeConstant
{
    /**
     * 私信
     */
    const PRIVATE_CHAT = 1;
    /**
     * 系统
     */
    const SYSTEM_CHAT = 2;
    /**
     * 频道信息
     */
    const CHANNEL_CHAT = 3;
    /**
     * 订阅频道
     */
    const CHANNEL_SUBSCRIBE = 4;
    /**
     * 历史消息
     */
    const CHANNEL_HISTORY = 5;

    public static function getTypes(): array
    {
        return [
            self::PRIVATE_CHAT,
            self::SYSTEM_CHAT,
            self::CHANNEL_CHAT,
            self::CHANNEL_SUBSCRIBE,
            self::CHANNEL_HISTORY,
        ];
    }
}