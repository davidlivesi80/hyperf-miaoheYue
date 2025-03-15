<?php

namespace App\Crontabs;

use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserRelationService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Upgrade", rule="\/20 * * * *", callback="execute", memo="自动升级任务")
 */

class Upgrade
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
        $info = $this->crontabService->findWhere('task_name','upgrade');
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

        //限制每日升级执行一次
        if($powerInsert->upgrade_time > 0){
            return false;
        }
        
        try {
             $orderCache = $this->getCache()->get('upgrade_'.date('Y-m-d'));
             if($orderCache){
                 $this->logger('[自动升级]','task')->info(json_encode(['msg'=>'正在执行,锁定中'],JSON_UNESCAPED_UNICODE));
                 return false;
             }
            $this->getCache()->set('upgrade_'.date('Y-m-d'),time());
            $start_time = $this->get_millisecond();
            $this->logger('[自动升级]','task')->info(json_encode(['msg'=>'本轮任务开始=='],JSON_UNESCAPED_UNICODE));
            /*条件*/
            $groups_rule = explode('@',$this->app(SysConfigService::class)->value('groups_rule'));
            $groups_nums = explode('@',$this->app(SysConfigService::class)->value('groups_nums'));
            $userSysIds =  $this->app(UserExtendService::class)->getQuery()->where('is_sys_level',1)->pluck('user_id')->toArray();
            /*执行V0*/
            $this->app(UserCountService::class)->getQuery()->whereNotIn('user_id',$userSysIds)->where('upgrade_time', '>',0)->select(['*'])->whereIn('user_id',function ($query){
                return $query->select('user_id')->from('user_extend')->where('level',0);
            })->chunkById(500, function ($lists) use ($groups_rule,$groups_nums) {
                foreach ($lists as $userCount){
                    $level = $this->app(UserCountService::class)->findLevel($userCount,$groups_rule,$groups_nums);
                    $extendInfo = $this->app(UserExtendService::class)->findByUid($userCount->user_id);
                    $levelData = $this->levelDate($level,$extendInfo,$extendInfo->level);
                    $this->app(UserExtendService::class)->getQuery()->where('user_id',$userCount->user_id)->update($levelData);
                    if($level != $extendInfo->level){
                        $this->logger('[自动升级-V1]','other')->info(json_encode(["用户$userCount->user_id,永久级别{$extendInfo->is_level},当前级别{$extendInfo->level},目标级别{$level}"],JSON_UNESCAPED_UNICODE));
                    }
                    if($level >= 1){
                        $this->app(UserCountService::class)->getQuery()->where(['id'=>$userCount->id])->update(['upgrade_time'=>time()]);
                    }else{
                        $this->app(UserCountService::class)->getQuery()->where(['id'=>$userCount->id])->update(['upgrade_time'=>0]);
                    }
                }
            });
            /*执行V1*/
            $this->app(UserCountService::class)->getQuery()->whereNotIn('user_id',$userSysIds)->select(['*'])->whereIn('user_id',function ($query){
                return $query->select('user_id')->from('user_extend')->where('level',1);
            })->chunkById(500, function ($lists) use ($groups_rule,$groups_nums) {
                foreach ($lists as $userCount){
                    $level = $this->app(UserCountService::class)->findLevel($userCount,$groups_rule,$groups_nums);
                    $extendInfo = $this->app(UserExtendService::class)->findByUid($userCount->user_id);
                    $levelData = $this->levelDate($level,$extendInfo,$extendInfo->level);
                    $this->app(UserExtendService::class)->getQuery()->where('user_id',$userCount->user_id)->update($levelData);
                    if($level != $extendInfo->level) {
                        $this->logger('[自动升级-V2]', 'other')->info(json_encode(["用户$userCount->user_id,永久级别{$extendInfo->is_level},当前级别{$extendInfo->level},目标级别{$level}"], JSON_UNESCAPED_UNICODE));
                    }
                }
            });
            /*执行V2*/
            $this->app(UserCountService::class)->getQuery()->whereNotIn('user_id',$userSysIds)->select(['*'])->whereIn('user_id',function ($query){
                return $query->select('user_id')->from('user_extend')->where('level',2);
            })->chunkById(500, function ($lists) use ($groups_rule,$groups_nums) {
                foreach ($lists as $userCount){
                    $level = $this->app(UserCountService::class)->findLevel($userCount,$groups_rule,$groups_nums);
                    $extendInfo = $this->app(UserExtendService::class)->findByUid($userCount->user_id);
                    $levelData = $this->levelDate($level,$extendInfo,$extendInfo->level);
                    $this->app(UserExtendService::class)->getQuery()->where('user_id',$userCount->user_id)->update($levelData);
                    if($level != $extendInfo->level) {
                        $this->logger('[自动升级-V3]', 'other')->info(json_encode(["用户$userCount->user_id,永久级别{$extendInfo->is_level},当前级别{$extendInfo->level},目标级别{$level}"], JSON_UNESCAPED_UNICODE));
                    }
                }
            });
            /*执行V3*/
            $this->app(UserCountService::class)->getQuery()->whereNotIn('user_id',$userSysIds)->select(['*'])->whereIn('user_id',function ($query){
                return $query->select('user_id')->from('user_extend')->where('level',3);
            })->chunkById(500, function ($lists) use ($groups_rule,$groups_nums) {
                foreach ($lists as $userCount){
                    $level = $this->app(UserCountService::class)->findLevel($userCount,$groups_rule,$groups_nums);
                    $extendInfo = $this->app(UserExtendService::class)->findByUid($userCount->user_id);
                    $levelData = $this->levelDate($level,$extendInfo,$extendInfo->level);
                    $this->app(UserExtendService::class)->getQuery()->where('user_id',$userCount->user_id)->update($levelData);
                    if($level != $extendInfo->level) {
                        $this->logger('[自动降级-V3]', 'other')->info(json_encode(["用户$userCount->user_id,永久级别{$extendInfo->is_level},当前级别{$extendInfo->level},目标级别{$level}"], JSON_UNESCAPED_UNICODE));
                    }
                }
            });

            //执行升级日限制
            Db::table('sys_power')->where('id',$powerInsert->id)->update(['upgrade_time'=>time()]);
            $this->getCache()->delete('upgrade_'.date('Y-m-d'));
            $run_time =  bcdiv(($this->get_millisecond() - $start_time),'1000',6);
            $this->logger('[自动升级]','task')->info(json_encode(['msg'=>'本轮任务完成=='.$run_time],JSON_UNESCAPED_UNICODE));
        } catch(\Throwable $e){
            $this->logger('[自动升级]','task')->info(json_encode(['msg'=>$e->getMessage(),'line'=>$e->getLine()],JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    public function levelDate($level,$extendInfo,$is_level){
        if( $extendInfo->last_level > 0 && time() >= $extendInfo->last_level){//永久级别变更
            $data = ['level'=>$level,'last_level'=>0,'is_level'=> $is_level];
        }else{
            if( $level > $extendInfo->level){
                $last_level = time() + 30 * 86400;
                $last_level = strtotime(date('Y-m-d H:59:59',$last_level));
                $data = ['level'=>$level,'last_level'=>$last_level];
            }elseif($level < $extendInfo->level){
                if($extendInfo->is_level  >= $level){//降低到上次永久级别
                    $level =   $extendInfo->is_level;
                    $data = ['level'=>$level,'last_level'=>0];
                }else{//降低到新级别
                    $last_level = time() + 30 * 86400;
                    $last_level = strtotime(date('Y-m-d H:59:59',$last_level));
                    $data = ['level'=>$level,'last_level'=>$last_level];
                }
            }elseif($level == $extendInfo->level){
                $data = ['level'=>$level];
            }
        }
        return $data;
    }

}