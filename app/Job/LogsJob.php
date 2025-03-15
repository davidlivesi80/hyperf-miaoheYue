<?php

namespace App\Job;

use App\Common\Service\System\SysLogsService;
use App\Common\Service\Rabc\AdminLogsService;
use Hyperf\AsyncQueue\Job;
use Upp\Traits\HelpTrait;
use Upp\Traits\QueueTrait;

class LogsJob extends Job
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

    public function handle()
    {
        try {

            if(!isset($this->params['type']) || !isset($this->params['data'])){
                return true;
            }

            if( $this->params['type'] == "sys"){
                $this->app(AdminLogsService::class)->create($this->params['data']);

            }elseif ($this->params['type'] == "api") {
                if($this->params['data']['path'] == "/api/second/create"){
                    $this->logger('[秒合约下单]','second')->info(json_encode($this->params['data'],JSON_UNESCAPED_UNICODE));
                }else{
                    $this->app(SysLogsService::class)->create($this->params['data']);
                }
            }
            return true;
        }catch (\Throwable $e){
            $this->logger('[日志队列异常]','error')->info( "LOGS消费失败-错误{$e->getMessage()}-行{$e->getLine()}-目录{$e->getFile()}");
        }
    }


    //发布消息
    public function dispatch(string $driverName = 'logs', int $delay = 0)
    {
        return $this->push($this->params,$driverName,$delay);
    }


}