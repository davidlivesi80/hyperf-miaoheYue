<?php

namespace App\Crontabs;

use App\Common\Service\System\SysCoinsService;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserRewardService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="InsertPower", rule="*\/20 * * * * *" , callback="execute", memo="复利矿池")
 */
class InsertPower
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
        $info = $this->crontabService->findWhere('task_name','insertPower');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        $strat_time = strtotime(date('Y-m-d') . ' ' . '00:05:00');
        if(time () < $strat_time){
            $this->logger('[复利矿池]','task')->info(json_encode(['msg'=>'等待00:10开始'],JSON_UNESCAPED_UNICODE));
            return false;
        }

        $powerInsert = Db::table('sys_power')->whereDate('created_at',date('Y-m-d'))->first();
        if ($powerInsert){
            return false;
        }

        Db::beginTransaction();
        try {

//             Db::table('user_power')->where(function ($query) {
//                     $query->where('wld', '>', 0)->orWhere('atm', '>', 0);
//                 })->update(['pools_wld'=> Db::raw('wld'),'pools_atm'=> Db::raw('atm')]);
             Db::table('sys_power')->insert([
                'power_wld' => 0,
                'power_atm'=> 0,
                'created_at'=>date('Y-m-d H:i:s')
             ]);

             $this->app(UserRewardService::class)->getQuery()->where(function ($query) {
                $query->where('income_today', '>', 0)->orWhere('deficit_today', '>', 0);
            })->update(['income_today'=>0,'deficit_today'=>0]);

             Db::commit();
             $this->logger('[复利矿池]','task')->info(json_encode(['msg'=>'本轮任务完成=='],JSON_UNESCAPED_UNICODE));

        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[复利矿池]','task')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
        }
    }
}