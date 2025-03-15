<?php
declare(strict_types=1);

namespace App\Common\Service\Push;

use App\Constant\PushEventConstant;
use App\Event\PushEvent;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

class PushMessageService
{
    use HelpTrait;
    /**
     * 发送频道消息
     *
     * @param array $message
     * @param array $loginParams
     * @return bool
     */
    public function channelMsgs(array $message, array $msgParams): bool
    {
        $this->handle($message, ['text' => mb_substr($msgParams['market'], 0, 30)]);

        return true;
    }

    /**
     * 处理数据
     *
     * @param  $record
     * @param array       $option
     */
    private function handle( $message, array $option = []): void
    {

        $this->event()->dispatch(new PushEvent(PushEventConstant::EVENT_TALK, [
            'sender_id'   => $message['sender_id'],
            'receiver_id' => $message['receiver_id'],
            'talk_type'   => $message['talk_type'],
            'record_id'   => $message['record_id']
        ]));
    }
}
