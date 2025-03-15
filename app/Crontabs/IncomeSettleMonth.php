<?php

namespace App\Crontabs;


use App\Common\Model\Users\UserSecondIncome;
use App\Common\Service\System\SysSecondService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserSecondIncomeService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserRewardService;
use Carbon\Carbon;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="IncomeSettleMonth", rule="\/59 * * * *", callback="execute", memo="上月盈利统计")
 */
class IncomeSettleMonth
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
        $info = $this->crontabService->findWhere('task_name','incomeSettleMonth');

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
        //清除数据
        $this->app(UserRewardService::class)->getQuery()->where(function ($query) {
            $query->where('income_month', '>', 0)->orWhere('deficit_month', '>', 0);
        })->update(['income_month'=>0,'deficit_month'=>0]);

        try {
            $orderCache = $this->getCache()->get('incomeSettleMonth_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[上月盈利统计]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('incomeSettleMonth_'.date('Y-m-d'),time(),86400);
            $lastmonth = Carbon::now();$startMonth = $lastmonth->startOfMonth()->timestamp;$endMonth = $lastmonth->endOfMonth()->timestamp;
            $start_time = $this->get_millisecond();
            $this->logger('[上月盈利统计','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $lists = $this->app(UserSecondIncomeService::class)->getQuery()->where('reward_time','>=' ,$startMonth)->where('reward_time',"<",$endMonth)->pluck('user_id')->toArray();
            $user_ids = array_values(array_unique($lists));
            foreach ($user_ids as $userId){
                $this->app(UserSecondService::class)->incomeSettleExtend($userId,3,$startMonth,$endMonth);
            }
            $this->getCache()->delete('incomeSettleMonth_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[上月盈利统计]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[上月盈利统计]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}