<?php

namespace App\Crontabs;



use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserWithdrawService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;

use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="WithdMsgs", rule="\/5 * * * *", callback="execute", memo="提现通知")
 */
class WithdMsgs
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
        $info = $this->crontabService->findWhere('task_name','withdMsgs');
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
            $created = date('Y-m-d H:i:s',(time() - 1200));
            $order = $this->app(UserWithdrawService::class)->getQuery()->where('created_at','>',$created)->whereIn('withdraw_status',[1])->count();
            if(!$order){
                return false;
            }
            $bot_token =  $this->app(SysConfigService::class)->value('withdraw_bot_token');
            $chat_id = $this->app(SysConfigService::class)->value('withdraw_chat_id');
            $text = $this->app(SysConfigService::class)->value('withdraw_msg');
            $this->telegramBotMessage($bot_token,$chat_id,$text);

        }catch (\Throwable $e){
            $this->logger('[提现通知]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}