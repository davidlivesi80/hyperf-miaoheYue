<?php

namespace App\Crontabs;


use App\Common\Service\System\SysSecondService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Income", rule="\/1 * * * * *", callback="execute", memo="盈利结算")
 */
class Income
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
    /** 绕过下单并发时间结算 58-03 秒*/
    public function execute()
    {
        $info = $this->crontabService->findWhere('task_name','income');
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

        $bing_strat = strtotime(date('Y-m-d H:i:56'));
        $bing_end =  strtotime(date('Y-m-d H:i:03'));
        $now_m_s = date('H:i');
        $configService= $this->app(SysConfigService::class);
        $second = $this->app(UserSecondService::class)->checkScene($now_m_s, $configService);
        if($second == 1 || $second == 2){
            if(time () >= $bing_strat || time () <= $bing_end){
                return false;
            }
        }

        try {
            $orderCache = $this->getCache()->get('incomeRobot_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[盈利结算]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('incomeRobot_'.date('Y-m-d'),time(),80);
            $start_time = $this->get_millisecond();
            //队列读取数据
            $maxList = $this->app(UserSecondService::class)->getQuery()->where('settle_status',0)->where('should_settle_time','<',$start_time)->whereIn('status',[1])->orderBy('id','asc')->limit(200)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('incomeRobot_'.date('Y-m-d'));
                $this->logger('[盈利结算]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[盈利结算]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserSecondService::class)->getQuery()->where('settle_status',0)->where('should_settle_time','<',$start_time)->whereIn('status',[1])->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $reward){
                        $this->app(UserSecondService::class)->income($reward);
                    }
                });
            $this->getCache()->delete('incomeRobot_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[盈利结算]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[盈利结算]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}