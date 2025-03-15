<?php

namespace App\Crontabs;


use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserRewardService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="SecondMsgs", rule="\/30 * * * * *", callback="execute", memo="盈亏通知")
 */
class SecondMsgs
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
        $info = $this->crontabService->findWhere('task_name','secondMsgs');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        $strat_time = strtotime(date('Y-m-d') . ' ' . '00:05:00');
        if(time () < $strat_time){
            return false;
        }

        try {
            //已执行
            $orderCountOne  = $this->getCache()->get('SecondMsgsOne_'.date('Y-m-d'));
            $orderCountOneArr = $orderCountOne ? json_decode($orderCountOne,true) : [];
            $orderCountTwo  = $this->getCache()->get('SecondMsgsTwo_'.date('Y-m-d'));
            $orderCountTwoArr = $orderCountTwo ? json_decode($orderCountTwo,true) : [];

            $second_today_limit = explode('@',$this->app(SysConfigService::class)->value('second_today_limit')) ;
            $second_today_limit_one = $second_today_limit[0];
            $second_today_limit_two = $second_today_limit[1];

            //未执行
            $orderCount300  = $this->app(UserRewardService::class)->getQuery()->whereNotIn('user_id', function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->where(function ($query) use ($second_today_limit_one,$second_today_limit_two){
                $query->whereRaw('(income_today - deficit_today) >= ' . $second_today_limit_one . ' and ' . '(income_today - deficit_today) < ' . $second_today_limit_two);
            })->pluck('user_id')->toArray();

            $orderCount1000  = $this->app(UserRewardService::class)->getQuery()->whereNotIn('user_id', function ($query){
                return $query->select('id')->from('user')->where('types',3);
            })->where(function ($query) use ($second_today_limit_two){
                $query->whereRaw('(income_today - deficit_today) >= ' . $second_today_limit_two);
            })->pluck('user_id')->toArray();

            if(count($orderCount300) > 0 || count($orderCount1000) > 0 ){
                $one_diff_uid =  array_diff($orderCount300,$orderCountOneArr);
                $two_diff_uid =  array_diff($orderCount1000,$orderCountTwoArr);
                if(count($one_diff_uid) > 0 || count($two_diff_uid) > 0){
                    $bot_token =  $this->app(SysConfigService::class)->value('chat_bot_token');
                    $chat_id = $this->app(SysConfigService::class)->value('chat_chat_id');
                    if(count($one_diff_uid) > 0){
                        $text = "【300】今日盈亏超额用户:" . implode(',',$one_diff_uid);
                    }
                    if( count($two_diff_uid) > 0){
                        $text = "【1000】今日盈亏超额用户:". implode(',',$two_diff_uid);
                    }
                    if(count($one_diff_uid) > 0 && count($two_diff_uid) > 0){
                        $text = "【300】今日盈亏超额用户:" . implode(',',$one_diff_uid) . "【1000】今日盈亏超额用户:". implode(',',$two_diff_uid);
                    }
                    $res = $this->telegramBotMessage($bot_token,$chat_id,$text);
                    //更新
                    $this->logger('[盈亏通知]','task')->info(json_encode(['msg'=>$res],JSON_UNESCAPED_UNICODE));
                }
                $this->getCache()->set('SecondMsgsOne_'.date('Y-m-d'), json_encode($orderCount300),86400);
                $this->getCache()->set('SecondMsgsTwo_'.date('Y-m-d'), json_encode($orderCount1000),86400);
            }

        }catch (\Throwable $e){
            $this->logger('[盈亏通知]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}