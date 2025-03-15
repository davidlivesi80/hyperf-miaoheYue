<?php

namespace App\Crontabs;


use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserSafetyOrderService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Peifu", rule="\/1 * * * *", callback="execute", memo="其他赔付")
 */
class Peifu
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

        $info = $this->crontabService->findWhere('task_name','peifu');
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

        //设置了赔付时间，则执行
        if(empty($powerInsert->safety_start)  || empty($powerInsert->safety_ends)){
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('peifu_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[其他赔付]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('peifu_'.date('Y-m-d'),time());
            $start_time = $this->get_millisecond();
            $user_ids = explode('@',$this->getCache()->get('peifu_list_user_ids'));
            $nums_ids = explode('@',$this->getCache()->get('peifu_list_nums_ids'));
            if(0>=count($user_ids) || 0>=count($nums_ids)){
                $this->logger('[保险赔付]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
            }
            $finish_ids = $this->getCache()->get('peifu_list_finish_ids');
            if($finish_ids){
                $finish_ids = explode('@',$this->getCache()->get('peifu_list_finish_ids'));
            }else{
                $finish_ids = [];
            }
            $this->logger('[其他赔付]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
             for ($i = 0; $i < count($user_ids); $i++) {
                 $userExtend = $this->app(UserExtendService::class)->getQuery()->where('user_id',$user_ids[$i])->first();
                 if($userExtend){
                     $res = $this->app(UserSafetyOrderService::class)->found($userExtend,$nums_ids[$i],$i);
                     if($res === true){
                         $finish_ids[] = $user_ids[$i];
                         $this->getCache()->set('peifu_list_finish_ids',implode('@',$finish_ids));
                     }
                 }
             }
            $this->getCache()->delete('peifu_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[其他赔付]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[其他赔付]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }



    }
}