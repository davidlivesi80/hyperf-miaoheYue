<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserLockedOrderService;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="RegisLockCLose", rule="\/1 * * * *", callback="execute", memo="注册赠送金超时")
 */
class RegisLockCLose
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

        $info = $this->crontabService->findWhere('task_name','regisLockClose');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }


        try {
            $orderCache = $this->getCache()->get('regisLockClose_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[注册赠送金超时]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('regisLockClose_'.date('Y-m-d'),time(),80);
            $start_time = $this->get_millisecond();
            $limit_time = time() - (11 * 86400); //获取11天前的注册用户，
            $maxList = $this->app(UserService::class)->getQuery()->where('is_lock',1)->where('created_at','<',date("Y-m-d H:i:s",$limit_time))->orderBy('id','asc')->limit(300)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('regisLockClose_'.date('Y-m-d'));
                $this->logger('[注册赠送金超时]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[注册赠送金超时]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserService::class)->getQuery()->where('is_lock',1)->where('created_at','<',date("Y-m-d H:i:s",$limit_time))->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $item){
                        if( 0 >= $this->app(UserLockedOrderService::class)->getQuery()->where('user_id',$item->id)->where('order_type',1)->count()){
                            $this->app(UserSecondService::class)->checkRegisLock($item->id,1);
                            $balance = $this->app(UserBalanceService::class)->findByUid($item->id);
                            $this->app(UserLockedOrderService::class)->create(['user_id'=>$item->id,'order_type'=>1,'order_num'=>$balance['usdt']]);
                        }
                    }
                });
            $this->getCache()->delete('regisLockClose_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[注册赠送金超时]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[注册赠送金超时]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }



    }
}