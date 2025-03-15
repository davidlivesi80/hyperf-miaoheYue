<?php

namespace App\Crontabs;


use App\Common\Service\System\SysRobotService;
use App\Common\Service\System\SysSecondService;
use App\Common\Service\Users\UserRadotService;
use App\Common\Service\Users\UserRechargeService;
use App\Common\Service\Users\UserRobotIncomeService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Reward", rule="\/2 * * * * *", callback="execute", memo="虚拟交易")
 */
class Reward
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
        $info = $this->crontabService->findWhere('task_name','reward');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('rewardRobot_'.date('Y-m-d'));
            if($orderCache){
                return false;
            }
            $this->getCache()->set('rewardRobot_'.date('Y-m-d'),time(),10);
            $start_time = $this->get_millisecond();
            $markets = $this->app(SysSecondService::class)->searchApi();
            foreach ($markets as $market){
                $userId = 1;
                $direct = mt_rand(1,2);
                $period = 60;
                $num    =  mt_rand(5,999);
                $this->app(UserSecondService::class)->found($userId, $market['market'],$direct,$period,$num);
            }
            $this->getCache()->delete('rewardRobot_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[虚拟交易]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[虚拟交易]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}