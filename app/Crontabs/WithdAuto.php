<?php

namespace App\Crontabs;



use App\Common\Service\Users\UserWithdrawService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Hyperf\DbConnection\Db;

use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="WithdAuto", rule="\/59 * * * * *", callback="execute", memo="提现自动")
 */
class WithdAuto
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
        $info = $this->crontabService->findWhere('task_name','withdAuto');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }

        $strat_time = strtotime(date('Y-m-d') . ' ' . '00:10:00');
        if(time () < $strat_time){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('withdAuto_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[提现自动]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('withdAuto_'.date('Y-m-d'),time(),80);
            
            $order = $this->app(UserWithdrawService::class)->getQuery()->where('audot_time',0)->whereIn('withdraw_status',[1])->orderBy('order_id','asc')->first();
            if(!$order){
                $this->getCache()->delete('withdAuto_'.date('Y-m-d'));
                $this->logger('[提现自动]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->app(UserWithdrawService::class)->audot($order->order_id);
            $this->getCache()->delete('withdAuto_'.date('Y-m-d'));
        }catch (\Throwable $e){
            $this->logger('[提现自动]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }
    }
}