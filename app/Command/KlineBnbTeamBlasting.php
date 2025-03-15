<?php
/**
 * K线团队爆破
*/
declare(strict_types=1);

namespace App\Command;

use App\Common\Service\Subscribe\SecondKline;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysSecondKlineService;
use App\Common\Service\System\SysSecondService;
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
class KlineBnbTeamBlasting extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  php bin/hyperf.php Kline:BnbTeamBlasting
     *
     * @var string
     */
    protected $name = 'Kline:BnbTeamBlasting';

    public function handle()
    {
        $this->line("开始执行++++++++++++++", 'info');
        $this->blastingKline();
    }

    /**
     * K线重置处理逻辑
     * @param $redis
     * @param $pattern
     * @param $chan
     * @param $msg
     */
    public function blastingKline(){
        while(true) {
            // 爆破开关
            $blasting_team_switch = $this->app(SysConfigService::class)->value('blasting_team_switch');
            $blasting_max_count = $this->app(SysConfigService::class)->value('blasting_team_count');
            $blasting_max_num = $this->app(SysConfigService::class)->value('blasting_team_num');

            $white_uids =  $this->app(UserService::class)->cacheableUserWhite();
            $white_user_ids =  $this->app(SysConfigService::class)->value('white_user_ids');
            $white_uids = $white_user_ids ? array_merge($white_uids, explode('@',$white_user_ids)) : $white_uids;

            if ($blasting_team_switch == 1) {
                $this->app(SysSecondKlineService::class)->bsettle_team($blasting_max_count,$blasting_max_num,$white_uids);
            }
            sleep(10);
        }
    }

}
