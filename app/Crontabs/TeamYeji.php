<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserCountService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="TeamYeji", rule="\/5 * * * *", callback="execute", memo="团队业绩任务")
 */
class TeamYeji
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

        $info = $this->crontabService->findWhere('task_name','teamYeji');
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
            $orderCache = $this->getCache()->get('teamYeji_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[团队业绩]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('teamYeji_'.date('Y-m-d'),time());
            $start_time = $this->get_millisecond();
            $this->logger('[团队业绩]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserCountService::class)->getQuery()->where('self_time','>',0)   //->where('team_time','<',strtotime(date('Y-m-d')))
                ->chunkById(500, function ($lists) {
                    foreach ($lists as $userCount){
                        $yeji = $this->app(UserCountService::class)->getTeamYeji($userCount->user_id);
                        $this->app(UserCountService::class)->getQuery()->where(['id'=>$userCount->id])->update(['team_time'=>time(),'team'=>$yeji,'self_time'=>0,'upgrade_time'=>time()]);
                        $this->logger('[团队业绩]','team')->info(json_encode(["用户$userCount->user_id,当前业绩{$yeji}"],JSON_UNESCAPED_UNICODE));
                    }
                });
            Db::table('sys_power')->where('id',$powerInsert->id)->update(['yeji_time'=>time()]);
            $this->getCache()->delete('teamYeji_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[团队业绩]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[团队业绩]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }

    }
}