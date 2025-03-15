<?php

namespace App\Job;


use App\Common\Service\Users\UserSecondService;
use Carbon\Carbon;
use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;
use Upp\Traits\QueueTrait;

class SelletJob extends Job
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
                $start_time = $this->get_millisecond();
                $reward = $this->app(UserSecondService::class)->getQuery()->where('settle_status',0)->where('should_settle_time','<=',$start_time)->where('id',$this->params['data']['id'])->first();
                if($reward){
                    $this->app(UserSecondService::class)->income($reward);
                }else {
                    $this->logger('[结算队列一异常]','error')->info("结算消费失败-没有数据" . $this->params['data']['id'] . $start_time);
                }
            } catch(\Throwable $e){
                //执行失败从新加入队列
                $this->logger('[结算队列一异常]','error')->info("结算消费失败-错误{$e->getMessage()}-行{$e->getLine()}-目录{$e->getFile()}");
            }
    }

    //发布消息
    public function dispatch(string $driverName = 'sellet', int $delay = 0)
    {
        return $this->push($this->params,$driverName,$delay);
    }


}