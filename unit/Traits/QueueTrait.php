<?php
namespace Upp\Traits;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Utils\ApplicationContext;

/**
 * Trait QueueTrait
 * @package Upp\traits
 */
trait QueueTrait
{
    /**
     * 生产消息.
     * @param $params 数据
     * @param int $delay 延时时间 单位秒
     */
    public function push($params,string $driverName = 'default', int $delay = 0): bool
    {
        $job_handle_name    = __CLASS__;
        $driver = ApplicationContext::getContainer()->get(DriverFactory::class)->get($driverName);
        return $driver->push(new $job_handle_name($params), $delay);
    }
}
