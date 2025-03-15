<?php
declare(strict_types=1);

namespace App\Support;

use App\Constant\PushModeConstant;

class Relation
{
    /**
     * 判断是否是好友或者群成员关系
     *
     * @param int $user_id     用户ID
     * @param int $receiver_id 接收者ID
     * @param int $talk_type   对话类型
     * @return bool
     */
    public static function isFriendOrGroupMember(int $user_id, int $receiver_id, int $talk_type): bool
    {
        if ($talk_type == PushModeConstant::PRIVATE_CHAT) {//私信消息对话
            return false;
        }else if ($talk_type == PushModeConstant::SYSTEM_CHAT) {//系统消息对话
            return true;
        }else if ($talk_type == PushModeConstant::CHANNEL_CHAT) {//频道消息对话
            return true;
        }

        return false;
    }
}
