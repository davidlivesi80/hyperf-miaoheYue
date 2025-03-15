<?php

namespace App\Crontabs;


use App\Common\Service\Subscribe\ChannelRecordData;
use Hyperf\Crontab\Annotation\Crontab;
use App\Common\Service\System\SysCrontabService;
use Upp\Service\BnbService;
use Upp\Traits\HelpTrait;
use Hyperf\DbConnection\Db;

/**
 * @Crontab(name="Market", rule="\/5 * * * * *", callback="execute", memo="采集任务")
 */
class Market
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
        $info = $this->crontabService->findWhere('task_name','market');
        if(!$info){
            return false;
        }
        if($info->status == 0){
            return false;
        }
        try {

            $markets = Db::table('sys_markets')->get()->toArray();
            foreach ($markets as $item) {
                $id = $item->id;
                $symbol = $item->symbol;
                $data['new_price']= $item->new_price;
                $data['old_price']=  $item->old_price;
                if(strtoupper($symbol) == 'BTCUSDT' || strtoupper($symbol) == 'ETHUSDT' || strtoupper($symbol) == 'BNBUSDT' || strtoupper($symbol) == 'TRXUSDT' || strtoupper($symbol) == 'SOLUSDT'){
                    $time = strtotime(date('Y-m-d 08:00:00'));
                    $recordId  =  strtolower($symbol.":1day:". $time);
                    //获取消息
                    usleep(500);
                    $result = $this->app(ChannelRecordData::class)->get($recordId,'time','market','type','open','close','low','high','vol','count','amount','ts');
                    if(!$result){
                        continue;
                    }
                    if($result['close'] == false || $result['open'] ==  false ){
                        $time = strtotime(date('Y-m-d 08:00:00')) - 86400;
                        $recordId  =  strtolower($symbol.":1day:". $time);
                        usleep(500);
                        $result = $this->app(ChannelRecordData::class)->get($recordId,'time','market','type','open','close','low','high','vol','count','amount','ts');
                    }

                    //获取昨天关盘价
                    //$this->logger('[采集任务]','task')->info("获取价格：{$recordId}-" . json_encode($result) );
                    if($result['close'] !=  $data['new_price'] || $result['open'] !=  $data['old_price'] ){
                        $data['new_price']= $result['close'];
                        $data['old_price']=  $result['open'];
                        $data['price_change']= bcmul( bcdiv(bcsub($result['close'], $result['open'],6),$result['open'],6),'100',4);
                    }
                }else{
                    $price_result = $this->http_request($symbol);
                    $data = [
                        'old_price'=>$price_result['lastPrice'],
                        'new_price'=>$price_result['lastPrice'],
                        'price_change'=>$price_result['priceChangePercent']
                    ];
                }

                Db::table('sys_markets')->where('id', $id)->update($data);

                sleep(3);
            }

        }catch (\Throwable $e){
            $this->logger('[采集任务]','task')->info(json_encode(['msg'=>$e->getMessage(),'line'=>$e->getLine()],JSON_UNESCAPED_UNICODE));
        }
    }

    private function http_request($symbol) {
        $uri = "https://api.binance.com/api/v3/ticker/24hr";
        $res = $this->GuzzleHttpGet($uri, ['symbol' => $symbol], "GET");
        $res = json_decode($res,true);
        return $res;
    }
}