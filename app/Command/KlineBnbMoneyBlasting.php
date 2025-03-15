<?php
/**
 * K线金额爆破
*/
declare(strict_types=1);

namespace App\Command;

use App\Common\Service\Subscribe\SecondKline;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysSecondKlineService;
use App\Common\Service\System\SysSecondService;
use App\Common\Service\Users\UserSecondService;
use App\Common\Service\Users\UserService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputArgument;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;
/**
 * @Command
 */
class KlineBnbMoneyBlasting extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  php bin/hyperf.php Kline:KlineBnbMoneyBlasting
     *
     * @var string
     */
    protected $name = 'Kline:KlineBnbMoneyBlasting';

    public function handle()
    {
        $this->line("开始执行++++++++++++++", 'info');
        $this->blastingMoneyKline();
    }

    /**
     * 金额定向爆破，大于等于指定金额
     * @param $redis
     * @param $pattern
     * @param $chan
     * @param $msg
     */
    public function blastingMoneyKline(){
        while(true) {
            // 爆破开关
            $blasting_money_switch = $this->app(SysConfigService::class)->value('blasting_money_switch');
            $blasting_money_amount = $this->app(SysConfigService::class)->value('blasting_money_amount');
            $blasting_user_ids=  $this->app(SysConfigService::class)->value('blasting_user_ids');
            $money_jian_kong_num=  $this->app(SysConfigService::class)->value('money_jian_kong_num');
            $blasting_user_ids=  $blasting_user_ids ? explode('@',$blasting_user_ids): [];
            $white_uids =  $this->app(UserService::class)->cacheableUserWhite();
            $white_user_ids =  $this->app(SysConfigService::class)->value('white_user_ids');
            $white_uids = $white_user_ids ? array_merge($white_uids, explode('@',$white_user_ids)) : $white_uids;

            $now_m_s = date('H:i');
            $configService= $this->app(SysConfigService::class);
            $second = $this->app(UserSecondService::class)->checkScene($now_m_s, $configService);
            if ($blasting_money_switch == 1 && $second != 2) { // 非带单时段
                $this->app(SysSecondKlineService::class)->bsettle_amount($blasting_money_amount,$blasting_user_ids,$white_uids,$money_jian_kong_num);
            }
            sleep(10);
        }
    }

}
