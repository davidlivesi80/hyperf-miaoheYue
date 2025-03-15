<?php

namespace App\Crontabs;

use App\Common\Service\Users\UserSecondQuickenService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="DnamicSettle", rule="\/2 * * * *", callback="execute", memo="秒合约动态-结算")
 */
class DnamicSettle
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
        $info = $this->crontabService->findWhere('task_name','dnamicSettle');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('dnamicSettle_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[秒合约动态-结算]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('dnamicSettle_'.date('Y-m-d'),time(),150);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserSecondQuickenService::class)->getQuery()->where('settle_time',0)->where('reward_type',1)->where("reward_time",'<',strtotime(date("Y-m-d")))->orderBy('id','asc')->limit(1200)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('dnamicSettle_'.date('Y-m-d'));
                $this->logger('[秒合约动态-结算]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[秒合约动态-结算]','task')->info(json_encode(['msg'=>'本轮任务开始=============='],JSON_UNESCAPED_UNICODE));
            $this->app(UserSecondQuickenService::class)->getQuery()->where('settle_time',0)->where('reward_type',1)->where("reward_time",'<',strtotime(date("Y-m-d")))->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $order){
                        $this->app(UserSecondService::class)->dnamicSettle($order);
                    }
                });
            $this->getCache()->delete('dnamicSettle_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[秒合约动态-结算]','task')->info(json_encode(['msg'=>'本轮任务完成=============='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[秒合约动态-结算]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}