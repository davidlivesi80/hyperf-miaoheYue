<?php
declare(strict_types=1);

namespace App\Common\Service\Push;

use App\Cache\VoteCache;
use App\Cache\VoteStatisticsCache;
use App\Constant\TalkMessageType;
use App\Model\Talk\TalkRecordsCode;
use App\Model\Talk\TalkRecordsFile;
use App\Model\Talk\TalkRecordsForward;
use App\Model\Talk\TalkRecordsInvite;
use App\Model\Talk\TalkRecordsLogin;
use App\Model\Talk\TalkRecordsMsgs;
use App\Model\Talk\TalkRecordsVote;
use App\Model\Talk\TalkRecordsChannel;
use App\Model\User;

class FormatMessageService
{
    /**
     * 格式化对话的消息体
     *
     * @param array $data 对话的消息
     * @return array
     */
    private function formatTalkMessage(array $data): array
    {
        $message = [
            "id"           => 0,  // 消息记录ID
            "talk_type"    => 1,  // 消息来源[1:私信;2:群聊,4频道]
            "msg_type"     => 1,  // 消息类型
            "user_id"      => 0,  // 发送者用户ID
            "receiver_id"  => 0,  // 接收者ID[好友ID或群ID或频道ID]
            // 不同的消息类型扩展
            "channel"      => [], // 频道消息
            "system"     => [], // 系统消息
            // 消息创建时间
            "content"      => '', // 文本消息
            "created_at"   => '', // 发送时间
        ];
        return array_merge($message, array_intersect_key($data, $message));
    }

    /**
     * 处理聊天记录信息
     *
     * @param array $rows 聊天记录
     * @return array
     */
    public function handleChatRecords(array $rows): array
    {
        if (!$rows) return [];

        $channel = [];
        foreach ($rows as $value) {
            switch ($value['msg_type']) {
                case TalkMessageType::CHANNEL_PUSH_MESSAGE:
                    $pushlist[] = $value['id'];
                    break;
                default:
            }
        }

        // 查询关注频道信息
        if ($channel) {
            $channel = TalkRecordsChannel::whereIn('record_id', $channel)->get([
                'record_id', 'type', 'operate_user_id', 'user_ids'
            ])->keyBy('record_id')->toArray();
        }

        foreach ($rows as $k => $row) {
            $rows[$k]['channel']     = [];
            switch ($row['msg_type']) {
                // 频道消息
                case TalkMessageType::CHANNEL_PUSH_MESSAGE:
                    if (isset($pushlist[$row['id']])) {
                        $rows[$k]['pushlist'] = $pushlist[$row['id']];
                        if(isset($rows[$k]['pushlist']['content'] ) && !empty($rows[$k]['pushlist']['content']) ){
                            $rows[$k]['pushlist']['content'] = htmlspecialchars_decode($rows[$k]['pushlist']['content']);
                        }

                    }
                    break;
            }

            $rows[$k] = $this->formatTalkMessage($rows[$k]);
        }

        return $rows;
    }
}
