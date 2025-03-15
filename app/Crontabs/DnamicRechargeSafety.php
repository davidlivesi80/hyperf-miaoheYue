<?php

namespace App\Crontabs;

use App\Common\Service\Users\UserRechargeService;
use App\Common\Service\Users\UserSecondQuickenService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="DnamicRechargeSafety", rule="\/3 * * * *", callback="execute", memo="推广充值送卷-结算")
 */
class DnamicRechargeSafety
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
        $info = $this->crontabService->findWhere('task_name','dnamicRechargeSafety');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('dnamicRechargeSafety_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[推广充值送卷-结算]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('dnamicRechargeSafety_'.date('Y-m-d'),time(),200);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserRechargeService::class)->getQuery()->where('recharge_safety','>',0)->where('safety_time',0)->orderBy('order_id','asc')->limit(300)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('dnamicRechargeSafety_'.date('Y-m-d'));
                $this->logger('[推广充值送卷-结算]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['order_id'];
            $this->logger('[推广充值送卷-结算]','task')->info(json_encode(['msg'=>'本轮任务开始=============='],JSON_UNESCAPED_UNICODE));
            $this->app(UserRechargeService::class)->getQuery()->where('recharge_safety','>',0)->where('safety_time',0)->where('order_id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $order){
                        $this->app(UserRechargeService::class)->dnamicSafety($order);
                    }
                });
            $this->getCache()->delete('dnamicRechargeSafety_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[推广充值送卷-结算]','task')->info(json_encode(['msg'=>'本轮任务完成=============='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[推广充值送卷-结算]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}