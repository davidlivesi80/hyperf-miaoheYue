<?php

namespace App\Crontabs;


use App\Common\Service\System\SysLOtteryService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserLotteryService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="IncomeLottery", rule="\/1 * * * *", callback="execute", memo="竞猜结算")
 */
class IncomeLottery
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
        $info = $this->crontabService->findWhere('task_name','incomeLottery');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        $powerInsert = Db::table('sys_power')->whereDate('created_at',date('Y-m-d'))->first();
        if (!$powerInsert){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('incomeLottery_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[竞猜结算]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('incomeLottery_'.date('Y-m-d'),time(),70);
            $start_time = $this->get_millisecond();
            $sellet_time = $this->app(UserLotteryService::class)->checkSettleTime();
            $sellet_time = strtotime($sellet_time . "00");
            $this->logger('[竞猜结算]','task')->info(json_encode(['msg'=>'时间'.$sellet_time],JSON_UNESCAPED_UNICODE));
            //队列读取数据
            $maxList = $this->app(UserLotteryService::class)->getQuery()->where('settle_status',0)->where('should_settle_time','<=',$sellet_time)->whereIn('status',[1])->orderBy('id','asc')->limit(500)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('incomeLottery_'.date('Y-m-d'));
                $this->logger('[竞猜结算]','task')->info(json_encode(['msg'=>'数据完成'.$sellet_time],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[竞猜结算]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserLotteryService::class)->getQuery()->where('settle_status',0)->where('should_settle_time','<',$sellet_time)->whereIn('status',[1])->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $reward){
                        $this->app(UserLotteryService::class)->income($reward);
                    }
                });
            $this->getCache()->delete('incomeLottery_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[竞猜结算]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[竞猜结算]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}