<?php

namespace Upp\Traits;


use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Server\ServerFactory;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

/**
 * Trait Help
 * @package Upp\traits
 */
trait HelpTrait
{
    /**
     * 容器实例
     */
    protected function app($id = null)
    {
        $container = ApplicationContext::getContainer();

        if($id){
            return $container->get($id);
        }

        return $container;
    }

    /**
     * event实例
     */
    protected function event()
    {
        return ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
    }

    /**
     * Server 实例 基于 Swoole Server
     *
     * @return \Swoole\Coroutine\Server|\Swoole\Server
     */
    protected static function server()
    {
        return ApplicationContext::getContainer()->get(ServerFactory::class)->getServer()->getServer();
    }

    /**
     * Server 实例 基于 Swoole Server
     *
     * @return \Swoole\Coroutine\Server|\Swoole\Server
     */
    protected static function frame()
    {
        return ApplicationContext::getContainer()->get(Frame::class);
    }

    /**
     * Server 实例 基于 Swoole Server
     *
     * @return \Swoole\Coroutine\Server|\Swoole\Server
     */
    protected static function webSocket()
    {
        return ApplicationContext::getContainer()->get(WebSocketServer::class);
    }

    /**
     * LOOGER实例
     */
    protected function logger($name = 'default',$type="default")
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name,$type);
    }

    /**
     * 控制台日志
     *
     * @return StdoutLoggerInterface|mixed
     */
    protected function stdout_log()
    {
        return ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
    }


    /**
     * 获取当前时间戳，毫秒
     *
     * @param int $len
     * @return bool|string
     */
    protected function get_millisecond($len=13) {
        [$msec, $sec] = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return substr($msectime . '', 0, $len);
    }

    /**
     * 设置错误信息
     * @param string|null $error
     * @return bool
     */
    protected function getCache()
    {
        return ApplicationContext::getContainer()->get(\Psr\SimpleCache\CacheInterface::class);
    }


    /**
     * 处理小数位
     *
     * @param $val
     * @param int $num
     * @param bool $rounding
     * @return float
     */
    protected function cus_floatval($val,$num=0,$rounding=false){
        if($num){
            if($rounding){
                $val=sprintf("%.".$num."f",$val);
            }else{
                $temp = intval(bcpow(10,$num));//次方
                $val = floor($val*$temp)/$temp;
            }
        }
        return floatval($val);

    }

    protected function cus_strval($val,$num){
        if(gettype($val)=='double'){
            $val = number_format($val, $num, '.', '');
        }else{
            $val = strval($val);
        }
        return $val;

    }

    protected function makeOrdersn($type = 'CZ'){
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        return  $type . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));
    }


    //中文json不转Uncode
    protected function mb_json_encode($str){
        return json_encode($str,JSON_UNESCAPED_UNICODE);
    }

    //网络相应
    private static function response($data){
        $response = ApplicationContext::getContainer()->get(ResponseInterface::class);
        return $response->withAddedHeader("Content-Type","application/json; charset=utf8")->withStatus(200)->withBody(new SwooleStream($data));
    }

    protected function json($message="",$code = 0,$data = []){
        $json = json_encode([
            'code' => $code,
            'message' => $message,
            'data' =>$data
        ], JSON_UNESCAPED_UNICODE);
        return self::response($json);
    }

    //获取真实IP
    public function getRealIp(RequestInterface $request)
    {
        $res = $request->getHeaders();
        if (isset($res['http_client_ip'])) {
            return $res['http_client_ip'];
        } elseif (isset($res['x-real-ip'])) {
            return $res['x-real-ip'];
        } elseif (isset($res['x-forwarded-for'])) {
            return $res['x-forwarded-for'];
        } elseif (isset($res['http_x_forwarded_for'])) {
            //部分CDN会获取多层代理IP，所以转成数组取第一个值
            $arr = explode(',', $res['http_x_forwarded_for']);
            return $arr[0];
        } else {
            $serverParams = $request->getServerParams();
            return $serverParams['remote_addr'] ?? '';
        }
    }

    /**
     * 数据列表转换成树
     *
     * @param  array   $dataArr   数据列表
     * @param  integer $rootId    根节点ID
     * @param  string  $pkName    主键
     * @param  string  $pIdName   父节点名称
     * @param  string  $childName 子节点名称
     * @return array  转换后的树
     */
    function ListToTree($dataArr, $rootId = 0, $pkName = 'id', $pIdName = 'parentId', $childName = 'children')
    {
        $tree = [];
        if (is_array($dataArr))
        {
            //1.0 创建基于主键的数组引用
            $referList  = [];
            foreach ($dataArr as $key => & $sorData)
            {
                $referList[$sorData[$pkName]] =& $dataArr[$key];
            }

            //2.0 list 转换为 tree
            foreach ($dataArr as $key => $data)
            {
                $pId = $data[$pIdName];
                if ($rootId == $pId) //一级
                {
                    $tree[] =& $dataArr[$key];
                }
                else //多级
                {
                    if (isset($referList[$pId]))
                    {
                        $pNode               =& $referList[$pId];
                        $pNode[$childName][] =& $dataArr[$key];
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys   要排序的键字段
     * @param string $sort  排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    function arraySort($array, $keys, $sort = SORT_DESC) {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /**
     * $prize_arr是一个二维数组，记录了所有本次抽奖的奖项信息，其中id表示中奖等级，prize表示奖品，v表示中奖概率。注意其中的v必须为整数，你可以将对应的奖项的v设置成0，即意味着该奖项抽中的几率是0，数组中v的总和（基数），基数越大越能体现概率的准确性。本例中v的总和为100，那么平板电脑对应的中奖概率就是1%，如果v的总和是10000，那中奖概率就是万分之一了。
     * $proArr = array(
     *      '0' => array('id'=>1,'prize'=>'平板电脑','v'=>1),
     *      '1' => array('id'=>2,'prize'=>'数码相机','v'=>5),
     *      '2' => array('id'=>3,'prize'=>'音箱设备','v'=>10),
     *      '3' => array('id'=>4,'prize'=>'4G优盘','v'=>12),
     *      '4' => array('id'=>5,'prize'=>'10Q币','v'=>22),
     *      '5' => array('id'=>6,'prize'=>'下次没准就能中哦','v'=>50),
     *  );
     */
    function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    /*网络请求*/
    function GuzzleHttpPost($uri='',$params=[]){
        // $options 等同于 GuzzleHttp\Client 构造函数的 $config 参数
        $options = [
            'timeout'  => 30.0,
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ],
        ];
        // $client 为协程化的 GuzzleHttp\Client 对象
        $client = $this->app(ClientFactory::class)->create($options);

        $response = $client->request("POST", $uri, [
            'json' => $params
        ]);

        $code = $response->getStatusCode();
        if($code != 200){
            return false;
        }
        $content = $response->getBody()->getContents();
        return $content;
    }
    
    function GuzzleHttpGet($uri='',$params=[]){
        // $options 等同于 GuzzleHttp\Client 构造函数的 $config 参数
        $options = [
            'timeout'  => 30.0,
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            ],
        ];
        // $client 为协程化的 GuzzleHttp\Client 对象
        $client = $this->app(ClientFactory::class)->create($options);
        $response = $client->request("GET", $uri, [
            'query' => $params
        ]);
        $code = $response->getStatusCode();
        if($code != 200){
            return false;
        }
        $content = $response->getBody()->getContents();
        return $content;
    }

    /**
     * IP操作限制
     */
    protected function limitIp($ip,$key = "",$timer=20)
    {
        $ip_lock =  $this->getCache()->get($key . $ip);
        if($ip_lock){
            return false;
        }
        $this->getCache()->set($key . $ip, 10, $timer);
        return true;
    }

     /**
     * 控k下跌。从大到小
     * @param $start 开始价格
     * @param $target 目标价格
     * @param $length 执行长度,
     * @param $precise 小数精度，
     * @return $decrements  下跌执行数据
     */
    function randomDecrementToTargetWithZero($start, $target, $length=5,$precise = '2') {
        // 确保起始值大于目标值
        if ($start <= $target) {
            return [];
        }
        $preciseNumArr = ['0'=>1,'2'=>100,'3'=>1000,'4'=>10000,'5'=>100000,'6'=>1000000];
        $preciseNum = $preciseNumArr[$precise];
        $decrements = [];
        $currentValue = $start;
        // 计算需要减少的总量
        $totalDecrement =  bcsub((string)$start,(string)$target,$precise);
        // 在 5 次中生成随机减少量
        for ($i = 1; $i <= ($length-1); $i++) {
            // 计算在剩余的次数中，最多可减去的量
            // 剩下的减少量不能为负,保留预设小数位
            $benDecrement =  bcsub((string)$totalDecrement,strval((($length-1) - $i) * 0),$precise);
            $maxDecrement = bcdiv((string)max(0,intval($benDecrement * $preciseNum)),(string)$preciseNum,$precise);
            // 随机选择减少量，确保不会超过所需的减少量，允许小数
            // 随机选择一个减少量，可能是 0 或者其他
            $decrement =  bcdiv((string)rand(0, intval($maxDecrement * $preciseNum)),(string)$preciseNum,$precise);
            // 确保每次循环随机有一次0的增幅
            $j = rand(1,15);
            if ($i == $j && !in_array(0,$decrements)) {
                $decrements[] = 0; // 插入零增幅
                continue; // 跳过当前循环，直接进入下一个循环
            }
            // 更新当前值和剩余总减少量
            $decrements[] =  $decrement;
            $currentValue =  bcsub((string)$currentValue,(string)$decrement,$precise);
            $totalDecrement = bcsub((string)$totalDecrement,(string)$decrement,$precise);
        }

        // 第五次直接把剩余的减少量减去
        $decrements[] = $totalDecrement;
        $currentValue = bcsub((string)$currentValue,(string)$totalDecrement,$precise);
        $this->logger('[控制K线]','kline')->info(json_encode(['初始值'=>$start,"减少次数"=>$length,'盘差' => $decrements,'目标值' => $currentValue]));
        return $decrements;
    }

    /**
     * 控k上涨。从小到大
     * @param $start 开始价格
     * @param $target 目标价格
     * @param $length 执行长度,
     * @param $precise 小数精度，
     * @return $increments  上涨执行数据
     */
    function randomIncrementToTargetWithZero($start, $target, $length=5,$precise = '2') {
        // 确保起始值小于目标值
        if ($start >= $target) {
            return [];
        }
        $preciseNumArr = ['0'=>1,'2'=>100,'3'=>1000,'4'=>10000,'5'=>100000,'6'=>1000000];
        $preciseNum = $preciseNumArr[$precise];
        $increments = [];
        $currentValue = $start;
        // 计算需要的增量总和
        $totalIncrement = bcsub((string)$target,(string)$start,$precise);
        // 在 5 次中生成随机增量
        for ($i = 1; $i <= ($length-1); $i++) {
            // 计算在剩余的次数中，最多可增量
             // 剩下的增量不能为负，保留预设小数位
            $benDecrement =  bcsub((string)$totalIncrement,strval((($length-1) - $i) * 0),$precise);
            $maxIncrement = bcdiv((string)max(0,intval($benDecrement * $preciseNum)),(string)$preciseNum,$precise);
            // 随机选择增量，确保不会超过所需的增量，允许小数
            // 随机选择一个增量，可能是 0 或者其他
            $increment = bcdiv((string)rand(0, intval($maxIncrement * $preciseNum)),(string)$preciseNum,$precise);
            // 确保每次循环随机有一次0的增幅
            $j = rand(1,15);
            if ($i == $j && !in_array(0,$increments)) {
                $increments[] = 0; // 插入零增幅
                continue; // 跳过当前循环，直接进入下一个循环
            }
            // 更新当前值和剩余总增量
            $increments[] = $increment;
            $currentValue = bcadd((string)$currentValue,(string)$increment,$precise);
            $totalIncrement = bcsub((string)$totalIncrement,(string)$increment,$precise);
        }

        // 第五次直接把剩余的增量加上去
        $increments[] = $totalIncrement;
        $currentValue = bcadd((string)$currentValue,(string)$totalIncrement,$precise);

        $this->logger('[控制K线]','kline')->info(json_encode(['初始值'=>$start,"增加次数"=>$length,'盘差' => $increments,'目标值' => $currentValue]));
        return $increments;
    }

    /*飞机机器人-推送消息到频道*/
    function telegramBotMessage ($bot_token,$chat_id,$text){
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $params = [
            'chat_id'=> $chat_id,
            'text'=> $text
        ];
        $res = $this->GuzzleHttpPost($url,$params);
        if(!$res){
            //写入错误日志
            return false;
        }
        return $res;

    }

}