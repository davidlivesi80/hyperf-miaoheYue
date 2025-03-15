<?php

namespace App\Crontabs;



use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserMessageService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;

use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="ChatMsgs", rule="\/3 * * * *", callback="execute", memo="留言通知")
 */
class ChatMsgs
{

    use HelpTrait;

    /**
     * @var SysCrontabService
     */
    private $crontabService;

    // 通过在构造函数的参数上声明参数类型完成自动注入
    public function __construct(SysCrontabService $crontabService)
    {
        $this->crontabService = $crontabService;

    }

    public function execute()
    {
        $info = $this->crontabService->findWhere('task_name','chatMsgs');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        $strat_time = strtotime(date('Y-m-d') . ' ' . '00:10:00');
        if(time () < $strat_time){
            return false;
        }

        try {
            $order = $this->app(UserMessageService::class)->getQuery()->where('is_reply',0)->where('is_send',0)->first();
            if(!$order){
                return false;
            }
            $bot_token =  $this->app(SysConfigService::class)->value('chat_bot_token');
            $chat_id = $this->app(SysConfigService::class)->value('chat_chat_id');
            $text = $this->app(SysConfigService::class)->value('chat_msg');
            $res = $this->telegramBotMessage($bot_token,$chat_id,$text);
            //更新
            $this->app(UserMessageService::class)->getQuery()->where('id',$order->id)->update(['is_send'=>time()]);
            $this->logger('[留言通知]','task')->info(json_encode(['msg'=>$res],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[留言通知]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}