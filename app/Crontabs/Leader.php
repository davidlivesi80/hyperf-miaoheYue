<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserLeaderService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Leader", rule="42 00 * * *", callback="execute", memo="渠道快照任务")
 */
class Leader
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

        $info = $this->crontabService->findWhere('task_name','leader');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('leader_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[渠道快照]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('leader_'.date('Y-m-d'),time());
            $start_time = $this->get_millisecond();
            $this->logger('[渠道快照]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserService::class)->getQuery()->where('types',2)->chunkById(100, function ($lists) {
                    foreach ($lists as $user){

                        $this->app(UserLeaderService::class)->create($user->id);
                    }
                });
            $this->getCache()->delete('leader_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[渠道快照]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[渠道快照]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }

    }
}