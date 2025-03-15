<?php

namespace App\Job;

use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserRelationService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserSecondIncomeService;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;
use Upp\Traits\QueueTrait;

class SecondJob extends Job
{
    use QueueTrait;
    use HelpTrait;

    public $params;

    /**
     * 任务执行失败后的重试次数，即最大执行次数为 $maxAttempts+1 次
     *
     * @var int
     */
    protected $maxAttempts = 2;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    //消费消息
    public function handle()
    {
            if (!isset($this->params['data']) || empty($this->params['data'])){
                return 0;
            }

            try {
                $res = $this->app(UserSecondService::class)->getQuery()->insertGetId($this->params['data']);
                if($res){//统计上周内流水
                    //组装队列数据
                    $sellet_data = ['id'=>$res,'should_settle_time'=>$this->params['data']['should_settle_time']];
                    //插入队列,计算延迟时间
                    $delay_time = bcsub((string)$this->params['data']['should_settle_time'],(string)$this->params['data']['time'],0);
                    $delay = bcdiv(strval($delay_time),strval(1000),0);
                    $rel = (new SelletJob(['data'=>$sellet_data]))->dispatch('sellet', $delay + 5);
                    if(!$rel){
                        $this->logger('[下单队列异常]','error')->info("创建结算失败".$res);
                    }
                    //执行统计
                    $now = Carbon::now();
                    $start = $now->startOfWeek()->subWeek()->timestamp; $ends = $now->endOfWeek()->timestamp;
                    //待优化。走缓存
                    $money = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$this->params['data']['user_id'])->where('reward_time', '>=', $start)->where('reward_time', '<=', $ends)->sum('total');
                    $this->app(UserCountService::class)->getQuery()->where('user_id',$this->params['data']['user_id'])->update(['last_time'=>time(),'money'=>$money]);
                    $parentIds = $this->app(UserRelationService::class)->getParent($this->params['data']['user_id']);
                    if(count($parentIds) > 0){
                        $this->app(UserCountService::class)->getQuery()->whereIn('user_id',$parentIds)->update(['liu_time'=>time(),'upgrade_time'=>time()]);
                    }
                }
            } catch(\Throwable $e){
                //执行失败从新加入队列
                $this->logger('[下单队列异常]','error')->info("跟单消费失败-错误{$e->getMessage()}-行{$e->getLine()}-目录{$e->getFile()}");
            }
    }

    //发布消息
    public function dispatch(string $driverName = 'second', int $delay = 0)
    {
        return $this->push($this->params,$driverName,$delay);
    }


}