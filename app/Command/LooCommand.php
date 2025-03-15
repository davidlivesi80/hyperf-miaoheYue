<?php

declare(strict_types=1);

namespace App\Command;


use App\Common\Service\Users\UserSecondIncomeService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserRechargeService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserBalanceLogService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserRelationService;
use App\Common\Service\Users\UserExtendService;
use App\Common\Service\Users\UserSafetyOrderService;
use App\Common\Service\Users\UserRewardService;
use Upp\Service\ParseToken;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;
use Carbon\Carbon;


/**
 * @Command
 */
class LooCommand extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  --- 移动关系 
     *
     * @var string
     */
    protected $name = 'loo:hello';

    public function handle()
    {
        // 通过内置方法 line 在 Console 输出 Hello Hyperf.

        //$aa = $this->randomIncrementToTargetWithZero(0.17884,0.17892,5,6);
        //$bb = $this->randomDecrementToTargetWithZero(91054.2,90991.30000000,5,2);
        //$this->line("结束++++++++++++++", 'info');
   
        $this->line("开始++++++++++++++", 'info');

        // $uid = 1268;
        // $pid = 497;
        // $this->app(UserService::class)->moveRelation($uid,$pid);

        // $cache = $this->getRedis()->keys('miaoheYue:user_parent_*');
        // if($cache){
        //     call_user_func_array([$this->getRedis(),'del'],$cache);
        // }
        $this->line("完成++++++++++++++", 'info');

        // $this->line("开始++++++++++++++自身业绩", 'info');

        // 上周流水更新
        // $now = Carbon::now();

        // $start = $now->startOfWeek()->subWeek()->timestamp; $ends = $now->endOfWeek()->timestamp;

        // $user_ids =  $this->app(UserSecondIncomeService::class)->getQuery()->where('reward_time', '>=', $start)->where('reward_time', '<=', $ends)->pluck('user_id');
        // for ($i = 0; $i < count($user_ids); $i++) {
        //     $uid = $user_ids[$i];
        //     $money = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$uid)->where('reward_time', '>=', $start)->where('reward_time', '<=', $ends)->sum('total');
        //     $this->app(UserCountService::class)->getQuery()->where('user_id',$uid)->update(['money'=>$money]);
        //     $parentIds = $this->app(UserRelationService::class)->getParent($uid);
        //     if(count($parentIds) > 0){
        //         $this->line(implode(',',$parentIds), 'info');
        //         $this->app(UserCountService::class)->getQuery()->whereIn('user_id',$parentIds)->update(['liu_time'=>time()]);
        //     }
        // }



        /*赔付*/
        // $user_ids =[
        //     1170,
        //     636
        //     ];
        // $nums_ids =[
        //     10,
        //     5
        //     ];
        // for ($i = 0; $i < count($user_ids); $i++) {
        //     $userExtend = $this->app(UserExtendService::class)->getQuery()->where('user_id',$user_ids[$i])->first();

        //     if($userExtend){
        //         $this->app(UserSafetyOrderService::class)->found($userExtend,$nums_ids[$i],$i);
        //     }
        // }

        //WHQD18920DE
//         for ($i = 1000; $i <= 5000; $i++) {
//             $data['method'] = 'email';
//             $data['email'] = 'vip' . $i . "@163.com";
//             $data['password'] = 'a123456';
//             $data['parent'] = 'TPO41A762F2';
//             $this->app(UserService::class)->create($data);
//         }

        for ($i = 4; $i < 1504; $i++) {
            $userInfo = $this->app(UserService::class)->find($i);
            $token = $this->app(ParseToken::class)->toToken($userInfo->id,$userInfo->username,'api');
            $this->logger('[注册]','other')->info($token['token'] . ',' . $userInfo->username);
        }


    }


}
