<?php
/**
 * 币安K线采集订阅
 *
 *  "e": "kline",     // 事件类型
    "E": 123456789,   // 事件时间
    "s": "BNBUSDT",   // 交易对
    "k": {
        "t": 123400000, // 这根K线的起始时间
        "T": 123460000, // 这根K线的结束时间
        "s": "BNBUSDT",  // 交易对
        "i": "1m",      // K线间隔
        "f": 100,       // 这根K线期间第一笔成交ID
        "L": 200,       // 这根K线期间末一笔成交ID
        "o": "0.0010",  // 这根K线期间第一笔成交价
        "c": "0.0020",  // 这根K线期间末一笔成交价
        "h": "0.0025",  // 这根K线期间最高成交价
        "l": "0.0015",  // 这根K线期间最低成交价
        "v": "1000",    // 这根K线期间成交量
        "n": 100,       // 这根K线期间成交笔数
        "x": false,     // 这根K线是否完结(是否已经开始下一根K线)
        "q": "1.0000",  // 这根K线期间成交额
        "V": "500",     // 主动买入的成交量
        "Q": "0.500",   // 主动买入的成交额
        "B": "123456"   // 忽略此参数
    }
*/
declare(strict_types=1);

namespace App\Command;

use App\Common\Service\Users\UserLotteryService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Utils\Coroutine;
use Symfony\Component\Console\Input\InputArgument;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;

/**
 * @Command
 */
class LotteryKlineSnSet extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  php bin/hyperf.php Kline:BnbReset
     *
     * @var string
     */
    protected $name = 'Lottery:KlineSn';

    public function handle()
    {
        $this->line("开始执行++++++++++++++", 'info');
        $this->resetKlineSn();
    }

    /**
     * K线重置处理逻辑
     * @param $redis
     * @param $pattern
     * @param $chan
     * @param $msg
     */
    public function resetKlineSn(){
        while(true) {
            $kline_key = implode(':',["btcusdt",'5min']);
            $this->app(UserLotteryService::class)->checkLottery(1,$kline_key);
            Coroutine::sleep(1);
        }
    }

}
