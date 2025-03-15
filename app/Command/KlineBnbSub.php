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

use App\Common\Service\System\SysSecondKlineService;
use App\Common\Service\System\SysSecondService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputArgument;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Client;
use Hyperf\WebSocketClient\Frame;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\Utils\Coroutine;
use Upp\Traits\HelpTrait;
use Upp\Traits\RedisTrait;



/**
 * @Command
 */
class KlineBnbSub extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  php bin/hyperf.php Kline:BnbSubscribe 1m
     *
     * @var string
     */
    protected $name = 'Kline:BnbSub';

    protected $client = null;

    public function configure()
    {
        /*period = [1m,5m,15m,30m,1h,1d,1w,1M]*/
        parent::configure();
        $this->addArgument('period', InputArgument::OPTIONAL, '订阅周期', '1m');
    }

    public function handle()
    {
        $this->line("开始采集++++++++++++++", 'info');
        $period = $this->input->getArgument('period');
        $this->collection($period);
    }

    /**
     * K线采集处理逻辑
     *
     * @param $redis
     * @param $pattern
     * @param $chan
     * @param $msg
     */
    public function collection($period){


        $markets = $this->app(SysSecondService::class)->searchApi();
        $params = [];
        foreach ($markets as $market) {
            if($market['status'] == 1){
                $params[] = strtolower($market['market']) . '@kline_' . $period;
            }
        }
        $data = [
            'method' => 'SUBSCRIBE', // SUBSCRIBE
            'id'     => time(),     // 1724348654
            'params' => ["btcusdt@kline_1m"],//交易对数组  ["btcusdt@kline_1m","bnbusdt@kline_1m"]
        ];
        $loop = \React\EventLoop\Loop::get();
        $connector = new \Ratchet\Client\Connector($loop);
        //while (true){
            $kline_key_period = "kline:". $period;
            $client = $this->getCache()->get($kline_key_period);
            //if(empty($client)){
                echo "111-{$client}\n";
                $CoroutineId = $this->app(Coroutine::class)->id();
                $this->getCache()->set($kline_key_period,$CoroutineId);
                $this->link($data,$kline_key_period,$connector,$loop);
                echo "2222-{$client}\n";
            //}
        //}


    }

    public function link($data,$kline_key_period,$connector,$loop){
        try {
            //ws://35.72.219.44:8989/wss/default.io  wss://stream.binance.com:9443/ws/btcusdt@depth-+
            $connector('wss://fstream.binance.com/stream?streams=btcusdt@kline_1m')->then(function(\Ratchet\Client\WebSocket $conn) use ($data,$kline_key_period,$connector,$loop){
                echo "0000000\n";
                $this->info("链接完成".$this->client."---" . date("Y-m-d H:i:s",$data['id']),'info');
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn) {
                    echo "Received: {$msg}\n";
                });

                $conn->on('close', function($code = null, $reason = null) use ($conn,$data,$kline_key_period,$connector,$loop){
                    echo "333333333333\n";
                    $conn->close();
                    $this->getCache()->delete($kline_key_period);
                    $this->info("链接关闭,重新链接{$code}-{$reason}--" . date("Y-m-d H:i:s"),'info');
                    //in 3 seconds the app will reconnect
                    $loop->addTimer(1, function () use ($data,$kline_key_period,$connector,$loop) {
                       $this->link($data,$kline_key_period,$connector,$loop);
                    });
                });

            }, function(\Exception $e) use ($loop,$kline_key_period) {
                echo "4444444444444\n";
                $this->getCache()->delete($kline_key_period);
                $this->info("链接失败：{$e->getMessage()}--" . date("Y-m-d H:i:s"),'info');
                $loop->stop();
            });

        }catch (\Throwable $e){
            $this->info("执行失败：{$e->getMessage()}--" . date("Y-m-d H:i:s"),'info');
        }
    }








}
