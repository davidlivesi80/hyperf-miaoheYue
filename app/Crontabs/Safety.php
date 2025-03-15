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
 * @Crontab(name="Safety", rule="\/5 * * * *", callback="execute", memo="保险赔付")
 */
class Safety
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

        $info = $this->crontabService->findWhere('task_name','safety');
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
            $orderCache = $this->getCache()->get('safety_'.date('Y-m-d'));
            if($orderCache){
                $this->logger('[保险赔付]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('safety_'.date('Y-m-d'),time());
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserExtendService::class)->getQuery()->where('safety_id','>',0)->where('last_safety','>=',time())->where('safety_time',0)->orderBy('id','asc')->limit(300)->get()->toArray();
            if(!$maxList){
                $this->getCache()->delete('safety_'.date('Y-m-d'));
                $this->logger('[保险赔付]','task')->info(json_encode(['msg'=>'数据完成'],JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[保险赔付]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            $this->app(UserExtendService::class)->getQuery()->where('safety_id','>',0)->where('last_safety','>=',time())->where('safety_time',0)->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) use ($powerInsert) {
                    foreach ($lists as $userExtend){
                        $this->app(UserSafetyOrderService::class)->create($userExtend,$powerInsert->safety_scene,$powerInsert->safety_start,$powerInsert->safety_ends,$powerInsert->dan5_start,$powerInsert->dan5_end);
                    }
                });
            $this->getCache()->delete('safety_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[保险赔付]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        }catch (\Throwable $e){
            $this->logger('[保险赔付]','task')->info(json_encode(['msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));
        }



    }
}