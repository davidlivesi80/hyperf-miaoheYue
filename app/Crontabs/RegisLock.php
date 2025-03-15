<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserLockedOrderService;
use App\Common\Service\Users\UserLockedService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="RegisLock", rule="\/3 * * * * *", callback="execute", memo="注册赠送金锁仓")
 */
class RegisLock
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

        $info = $this->crontabService->findWhere('task_name','regisLock');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }


        try {
            $orderCache = $this->getCache()->get('regisLock_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[注册赠送金锁仓]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('regisLock_'.date('Y-m-d'),time(),5);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserLockedOrderService::class)->getQuery()->where('lock_time',0)->orderBy('id','asc')->limit(50)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('regisLock_'.date('Y-m-d'));
                $this->logger('[注册赠送金锁仓]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[注册赠送金锁仓]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserLockedOrderService::class)->getQuery()->where('lock_time',0)->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $item){
                        $res = $this->app(UserLockedService::class)->lock($item->user_id,$item->order_num,1,1);
                        if($res === true){
                            $this->app(UserLockedOrderService::class)->getQuery()->where('id',$item->id)->update(['lock_time'=>time()]);
                            //体验金余额锁仓完成，解除体验限制
                            $this->app(UserSecondService::class)->checkRegisLock($item->user_id,3);
                        }
                    }
                });
            $this->getCache()->delete('regisLock_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[注册赠送金锁仓]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[注册赠送金锁仓]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }



    }
}