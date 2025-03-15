<?php

declare(strict_types=1);

namespace App\Command;


use App\Common\Service\System\SysRobotService;
use App\Common\Service\System\SysSwapService;
use App\Common\Service\System\SysExchangeService;
use App\Common\Service\Users\UserExchangeService;
use App\Common\Service\Users\UserWithdrawService;
use App\Common\Service\Users\UserPowerOrderService;
use App\Common\Service\Users\UserPowerService;
use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserSwapService;
use App\Common\Service\Users\UserRadotService;
use App\Common\Service\Users\UserRaqueService;
use App\Common\Service\Users\UserReserveService;
use App\Common\Service\Users\UserTransferService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use App\Common\Service\Users\UserService;
use App\Common\Service\Users\UserRobotService;
use App\Common\Service\Users\UserRobotQuickenService;
use App\Common\Service\Users\UserRebotService;
use App\Common\Service\Users\UserRebotQuickenService;
use App\Common\Service\Users\UserCountService;
use App\Common\Service\Users\UserRelationService;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;

/**
 * @Command
 */
class PooCommand extends HyperfCommand
{
    use HelpTrait;
    /**
     * 执行的命令行   --- 插入导入投资订单
     *
     * @var string
     */
    protected $name = 'poo:hello';

    public function handle()
    {
        // 通过内置方法 line 在 Console 输出 Hello Hyperf.

        $this->line("开始++++++++++++++", 'info');


        $ids = [
            24904010,
            25216772,
            25500807,
            25788400,
            26079165,
            26374565,
            26681518,
            26986542,
            27297183,
            27604749,
            27916850,
            28233242,
            28554831,
            28882495,
            29214479,
            29552960,
            29879643,
            29989804,
            30191125,
            30399237,
            30863463,
            31155761,
            31660206,
            32391736,
            32700047,
            33059146,
            33439965,
            33827174,
            34222315,
            34613433,
            35009906,
            35413405,
            35824545,
            36234761,
            36697622,
            37116917,
            37537818,
            37968681,
            38417231,
            38899616,
            39384473,
            39842769,
            40312461,
            40797553,
            41279752,
            41763870,
            42261673,
            42756096,
            43237852,
            43732242,
            44214327,
            44705825,
            45197816,
            45700968,
            46245659,
            46796797,
            47335499,
            47875953,
            48337572,
            48800140,
            49262630,
        ];
        $num = [
            13.87955300,
            14.16028800,
            14.89541800,
            14.00169300,
            11.61002700,
            12.05795100,
            9.62977500,
            10.36855200,
            10.91494600,
            10.57849300,
            10.42413100,
            9.20679400,
            8.99620500,
            9.19228800,
            9.41733400,
            12.42165800,
            12.47478000,
            10.93882300,
            11.59848700,
            11.54493200,
            12.13739000,
            11.96725900,
            11.82575400,
            12.69649400,
            14.31080700,
            15.68290000,
            15.03618200,
            16.22068300,
            15.92549300,
            15.06854600,
            14.88908200,
            15.34600300,
            15.97637300,
            16.00559300,
            15.67587700,
            16.60542400,
            16.86138400,
            16.61330500,
            15.91825100,
            15.40679300,
            14.60944600,
            14.73242100,
            14.63999700,
            13.22411500,
            14.97187000,
            16.04962500,
            16.62119300,
            16.34947800,
            15.79613400,
            17.05028400,
            17.76864600,
            17.20955400,
            16.12355300,
            16.18318700,
            15.16647900,
            16.49586800,
            17.47590300,
            18.61927300,
            16.79665700,
            14.88908200,
            15.79613400,
        ];
        $old = 1547.84017600;

        for ($i=0; $i < count($ids); $i++){
            $oldnew = $old;
            $new = $old = bcadd((string)$old,(string)$num[$i],6);
            $this->line("WHEN {$ids[$i]} THEN {$oldnew}", 'info');
        }
        $this->line("分割++++++++++++++", 'info');
        $old = 1547.84017600;
        for ($i=0; $i < count($ids); $i++){
            $oldnew = $old;
            $new = $old = bcadd((string)$old,(string)$num[$i],6);
            $this->line("WHEN {$ids[$i]} THEN {$new}", 'info');
        }


        $this->line("完成++++++++++++++", 'info');
    }





}
