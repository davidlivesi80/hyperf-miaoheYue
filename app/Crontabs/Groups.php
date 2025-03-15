<?php

namespace App\Crontabs;

use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Groups", rule="\/3 * * * *", callback="execute", memo="秒合约团队")
 */
class Groups
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
        $info = $this->crontabService->findWhere('task_name','groups');
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
        //限制每周执行一次
        if(0>=$powerInsert->upgrade_time){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('groupsRobot_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[秒合约团队]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('groupsRobot_'.date('Y-m-d'),time(),200);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserExtendService::class)->getQuery()->where('level',3)->where('last_groups',1)->orderBy('id','asc')->limit(180)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('groupsRobot_'.date('Y-m-d'));
                $this->logger('[秒合约团队]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[秒合约团队]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $groupRate = $this->app(SysConfigService::class)->value('groups_rate');
            $this->app(UserExtendService::class)->getQuery()->where('level',3)->where('last_groups',1)->where('id', '<=', $maxIds)->chunkById(60, function ($lists) use ($groupRate) {
                    foreach ($lists as $order){
                        $this->app(UserSecondService::class)->groups($order,$groupRate);
                    }
                });
            $this->getCache()->delete('groupsRobot_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[秒合约团队]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[秒合约团队]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}