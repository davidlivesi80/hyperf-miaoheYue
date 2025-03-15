<?php

namespace App\Crontabs;

use App\Common\Service\Users\UserRobotIncomeService;
use App\Common\Service\Users\UserRobotQuickenService;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\Users\UserSecondQuickenService;
use App\Common\Service\Users\UserSecondService;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use App\Common\Service\System\SysConfigService;

use Upp\Traits\HelpTrait;

/**
 * @Crontab(name="Quicken", rule="\/1 * * * *", callback="execute", memo="动态奖金统计")
 */
class Quicken
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
        $info = $this->crontabService->findWhere('task_name', 'quicken');
        if (!$info) {
            return false;
        }
        if ($info->status == 0) {
            return false;
        }

        try {
            $orderCache = $this->getCache()->get('quickenRobot_' . date('Y-m-d'));
            if ($orderCache) {
                $this->logger('[动态奖金统计]', 'task')->info(json_encode(['msg' => '正在执行,锁定中'], JSON_UNESCAPED_UNICODE));
                return false;
            }
            $this->getCache()->set('quickenRobot_' . date('Y-m-d'), time(), 80);
            $start_time = $this->get_millisecond();
            $maxList = $this->app(UserSecondQuickenService::class)->getQuery()->where('quicken_time', 0)->where('settle_time','>', 0)->orderBy('id', 'asc')->limit(300)->get()->toArray();
            if (!$maxList) {
                $this->getCache()->delete('quickenRobot_' . date('Y-m-d'));
                $this->logger('[动态奖金统计]', 'task')->info(json_encode(['msg' => '数据完成'], JSON_UNESCAPED_UNICODE));
                return false;
            }
            $maxIds =$maxList[count($maxList) - 1]['id'];
            $this->logger('[动态奖金统计]', 'task')->info(json_encode(['msg' => '本轮任务开始=============='], JSON_UNESCAPED_UNICODE));
            $this->app(UserSecondQuickenService::class)->getQuery()->where('quicken_time', 0)->where('settle_time','>', 0)->where('id', '<=', $maxIds)
                ->chunkById(100, function ($lists) {
                    foreach ($lists as $order) {
                        $this->app(UserSecondService::class)->quicken($order);
                    }
                });
            $this->getCache()->delete('quickenRobot_' . date('Y-m-d'));
            $run_time = bcdiv(($this->get_millisecond() - $start_time), '1000', 6);
            $this->logger('[动态奖金统计]', 'task')->info(json_encode(['msg' => '本轮任务完成==============' . $run_time], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            $this->logger('[动态奖金统计]', 'task')->info(json_encode(['msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
        }
    }
}