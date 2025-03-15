<?php
// +----------------------------------------------------------------------
// | UINT
// +----------------------------------------------------------------------
namespace Upp\Traits;

use Hyperf\Redis\Redis;
use Hyperf\Utils\ApplicationContext;
use Upp\Repository\Subscriber;

/**
 *
 * Class BaseError
 * @package crmeb\basic
 */
trait RedisTrait
{
    /**
     * 错误信息
     * @var string
     */
    protected $redis;


    /**
     * 设置错误信息
     * @param string|null $error
     * @return bool
     */
    protected function getRedis()
    {
        return ApplicationContext::getContainer()->get(Redis::class);
    }

    /**
     * 设置错误信息
     * @param string|null $error
     * @return bool
     */
    protected function getSubRedis()
    {
        return ApplicationContext::getContainer()->get(Subscriber::class);
    }

    /**
     * 推送消息到 Redis 订阅中
     *
     * @param string       $chan
     * @param string|array $message
     */
    protected  static function push_redis_subscribe(string $chan, $message)
    {
        ApplicationContext::getContainer()->get(Redis::class)->publish($chan, is_string($message) ? $message : json_encode($message));
    }




}
