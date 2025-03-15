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


use App\Common\Service\System\SysConfigService;
use App\Common\Service\Users\UserSecondService;
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
class CopingSecond extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  php bin/hyperf.php Coping:Second
     *
     * @var string
     */
    protected $name = 'Coping:Second';

    public function handle()
    {
        $this->line("开始执行++++++++++++++", 'info');
        $this->runing();
    }

    /**
     * K线重置处理逻辑
     * @param $redis
     * @param $pattern
     * @param $chan
     * @param $msg
     */
    public function runing(){
        while(true) {
            $userIds = [5038,5044];
            /** @var  $ConfigService ConfigService*/
            $configService= $this->app(SysConfigService::class);
            // 检验获取场次校验
            $now_m_s = date('H:i');
            $scene = $this->app(UserSecondService::class)->checkScene($now_m_s,$configService);
            if($scene !=2){
                $this->line("时间未倒！！", 'info');
                Coroutine::sleep(30);
                continue;
            }
            try {
                foreach ($userIds as $uid){
                    Coroutine::sleep(0.3);
                    $this->app(UserSecondService::class)->coping($uid,$scene,$configService);
                }
            } catch(\Throwable $e){
                //写入错误日志
                $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
                $this->line(json_encode($error,JSON_UNESCAPED_UNICODE), 'info');
            }
        }
    }

}
