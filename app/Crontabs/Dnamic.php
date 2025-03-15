<?php

namespace App\Crontabs;

use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserSecondIncomeService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Dnamic", rule="\/2 * * * *", callback="execute", memo="秒合约动态-预算")
 */
class Dnamic
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
        $info = $this->crontabService->findWhere('task_name','dnamic');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('dnamicRobot_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[秒合约动态-预算]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('dnamicRobot_'.date('Y-m-d'),time(),150);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserSecondIncomeService::class)->getQuery()->where('dnamic_time',0)->orderBy('id','asc')->limit(180)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('dnamicRobot_'.date('Y-m-d'));
                $this->logger('[秒合约动态-预算]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[秒合约动态-预算]','task')->info(json_encode(['msg'=>'本轮任务开始=============='],JSON_UNESCAPED_UNICODE));
            $dnamicRate = $this->app(SysConfigService::class)->value('dnamic_rate');
            $dnamicRateArr = explode('@',$dnamicRate);
            $this->app(UserSecondIncomeService::class)->getQuery()->where('dnamic_time',0)->where('id', '<=', $maxIds)
                ->chunkById(60, function ($lists) use ($dnamicRateArr) {
                    foreach ($lists as $order){
                        $this->app(UserSecondService::class)->dnamic($order, $dnamicRateArr);
                    }
                });
            $this->getCache()->delete('dnamicRobot_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[秒合约动态-预算]','task')->info(json_encode(['msg'=>'本轮任务完成=============='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[秒合约动态-预算]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}