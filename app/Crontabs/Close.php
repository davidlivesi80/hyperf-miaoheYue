<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserRobotService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * Crontab(name="Close", rule="\/1 * * * *", callback="execute", memo="返还订单")
 */
class Close
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
        $info = $this->crontabService->findWhere('task_name','close');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        $strat_time = strtotime(date('Y-m-d') . ' ' . '04:00:00');
        if(time () < $strat_time){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('close_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[返还订单]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('close_'.date('Y-m-d'),time(),80);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserRobotService::class)->getQuery()->where('status',3)->orderBy('id','asc')->limit(300)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('unlock_'.date('Y-m-d'));
                $this->logger('[返还订单]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]->id;
            $this->logger('[返还订单]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserRobotService::class)->getQuery()->where('status',3)->chunkById(100, function ($lists) {
                    foreach ($lists as $order){
                        $this->app(UserRobotService::class)->cancel($order);
                    }
                });
            $this->getCache()->delete('close_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[返还订单]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[返还订单]','task')->info(json_encode(['msg'=>$e->getMessage(),'line'=>$e->getLine()],JSON_UNESCAPED_UNICODE));
        }
    }
}