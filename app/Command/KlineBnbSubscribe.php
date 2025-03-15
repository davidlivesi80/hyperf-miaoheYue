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
class KlineBnbSubscribe extends HyperfCommand
{
    use HelpTrait;
    use RedisTrait;
    /**
     * 执行的命令行  php bin/hyperf.php Kline:BnbSubscribe 1m
     *
     * @var string
     */
    protected $name = 'Kline:BnbSubscribe';

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
        if(count($params) > 0){
            //  wss://fstream.binance.com/stream?streams=btcusdt@kline_1m/bnbusdt@kline_1m
            // 对端服务的地址，如没有提供 ws:// 或 wss:// 前缀，则默认补充 ws://
            $url = 'wss://fstream.binance.com/stream?streams';
            // 通过 ClientFactory 创建 Client 对象，创建出来的对象为短生命周期对象
            $this->client = $this->app(ClientFactory::class)->create($url,false);
            $data = [
                'method' => 'SUBSCRIBE', // SUBSCRIBE
                'id'     => time(),     // 1724348654
                'params' => $params,//交易对数组  ["btcusdt@kline_1m","bnbusdt@kline_1m"]
            ];
            //每24小时从新订阅一次
           $this->client->push(json_encode($data));
            while (true){
                if(empty($this->client)){
                    $CoroutineId = $this->app(Coroutine::class)->id();
                    $this->client = $this->app(ClientFactory::class)->create($url,false);
                    $data = [
                        'method' => 'SUBSCRIBE', // SUBSCRIBE
                        'id'     => time(),     // 1724348654
                        'params' => $params,//交易对数组  ["btcusdt@kline_1m","bnbusdt@kline_1m"]
                    ];
                    $res = $this->client->push(json_encode($data));
                    $this->info("重新链接,链接完成{$CoroutineId}---" . date("Y-m-d H:i:s",$data['id']),'info');
                    Coroutine::sleep(0.5);
                    continue;
                }
                /** @var Frame $message */
                $message =  $this->client->recv(3);
                if (!$message){
                    $CoroutineId = $this->app(Coroutine::class)->id();
                    $this->client->close();
                    $this->client = null;
                    $this->info("链接关闭,重新链接{$CoroutineId}---" . date("Y-m-d H:i:s"),'info');
                    Coroutine::sleep(0.5);
                    continue;
                }
                $messageData = json_decode($message->data, true);
                $rel = $this->app(SysSecondKlineService::class)->collection($messageData);
                if($rel !== true){
                    if($rel == 1){
                        $this->line("采集失败:stream数据不存在获取错误", 'info');
                    }elseif($rel == 2){
                        $this->line("采集失败:market或者t数据不存在", 'info');
                    }elseif($rel == 3){
                        $this->line("采集失败:交易量未变不写入", 'info');
                    }else{
                        $this->line("采集失败:其他错误，error日志", 'info');
                    }
                }
            }
        }

    }






}
