<?php
declare(strict_types=1);

namespace App\Process;


use App\Constant\RedisSubscribeChan;
use App\Common\Service\Push\SubscribeHandleService;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;

/**
 * Websocket 消息订阅处理服务
 * @Process(name="RedisWebsocketSubscribe")
 */
class RedisWebsocketSubscribe extends AbstractProcess
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 订阅的通道
     *
     * @var string[]
     */
    private $chans = [
        RedisSubscribeChan::WEBSOCKET_CHAN
    ];

    /**
     * @var SubscribeHandleService
     */
    private $handleService;

    /**
     * 执行入口
     */
    public function handle(): void
    {
        $this->handleService = $this->app(SubscribeHandleService::class);
        try {
            $this->getSubRedis()->subscribe($this->chans, [$this, 'subscribe']);

        } catch (\Throwable $e){
            echo "订阅执行错误信息：" . $e->getMessage();
        }
    }

    /**
     * 订阅处理逻辑
     *
     * @param        $redis
     * @param string $chan
     * @param string $message
     */
    public function subscribe($redis, string $chan, string $message)
    {
        $data = json_decode($message, true);
        try {
            $this->handleService->handle($data);
        } catch (\Throwable $e){
            echo "订阅处理错误信息：" . $e->getMessage();
        }
    }
}
