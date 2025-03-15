<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserCountService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="TotalYeji", rule="\/5 * * * *", callback="execute", memo="团队流水任务")
 */
class TotalYeji
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

        $info = $this->crontabService->findWhere('task_name','totalYeji');
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
            $orderCache = $this->getCache()->get('totalYeji_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[团队流水]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('totalYeji_'.date('Y-m-d'),time());
            $start_time = $this->get_millisecond();
            $this->logger('[团队流水]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserCountService::class)->getQuery()->where('liu_time','>',0)   //->where('team_time','<',strtotime(date('Y-m-d')))
            ->chunkById(500, function ($lists) {
                foreach ($lists as $userCount){
                    $total = $this->app(UserCountService::class)->getTotalYeji($userCount->user_id);
                    $this->app(UserCountService::class)->getQuery()->where(['id'=>$userCount->id])->update(['total'=>$total,'liu_time'=>0]);
                    $this->logger('[团队流水]','team')->info(json_encode(["用户$userCount->user_id,当前流水{$total}"],JSON_UNESCAPED_UNICODE));
                }
            });
            Db::table('sys_power')->where('id',$powerInsert->id)->update(['liu_time'=>time()]);
            $this->getCache()->delete('totalYeji_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[团队流水]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[团队流水]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }

    }
}