<?php


namespace App\Common\Service\System;


use App\Common\Logic\System\SysSecondKlineLogic;
use App\Common\Model\System\SysSecondKline;
use App\Common\Service\Subscribe\ChannelRecord;
use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\Subscribe\SecondKline;
use App\Common\Service\Subscribe\SecondKlineData;
use App\Common\Service\Users\UserSecondService;
use App\Constant\ChannelTimeConstant;
use Upp\Basic\BaseService;
use Upp\Traits\HelpTrait;


class SysSecondKlineService extends BaseService
{
    use HelpTrait;
    /**
     * @var SysSecondKlineLogic
     */
    public function __construct(SysSecondKlineLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);
        return $list;
    }

    // k线操作 - 采集
    public function collection($data){
        try {
            if(!isset($data['stream'])){
                if(isset($data['error'])){
                    $this->logger('[采集K线]','error')->info(json_encode($data['error'],JSON_UNESCAPED_UNICODE));
                }
                return 1;
            }

            $market = strtolower($data['data']['s']);
            $t = $data['data']['k']['i'];
            if (!$market || !$t) return 2;
            $tick = $data['data']['k'];
            $time = intval($tick['t'] / 1000);
            //时间戳顺序缓存
            $recordId =  "{$market}:". ChannelTimeConstant::CHANNEL_TIMES[$t];
            $this->app(ChannelRecord::class)->addRecordId($recordId,$time,(string)$time);
            //对应时间戳数据
            $recordIdKey = "{$market}:". ChannelTimeConstant::CHANNEL_TIMES[$t] .":". $time;
            $res_all = $this->app(ChannelRecordData::class)->all($recordIdKey);
//            if ($res_all && isset($res_all['amount'])  ) {// 这根K线期间成交额没有变化，则不更新Kxian
//                if($res_all['amount'] > 0 && floatval($res_all['amount']) == floatval($tick['q'])){
//                    return 3;
//                }
//            }

            $item = [
                'time'   => $time,
                'market' => $market,
                'type'   => ChannelTimeConstant::CHANNEL_TIMES[$t],
                'open'   => floatval($tick['o']),
                'close'  => floatval($tick['c']),
                'low'    => floatval($tick['l']),
                'high'   => floatval($tick['h']),
                'vol'    => floatval($tick['v']),
                'count'  => intval($tick['n']),
                'amount' => floatval($tick['q']),
                'ts'     => $this->get_millisecond()
            ];

            //一分钟控K开始-当前秒是不是预设了
            if ($t == '1m') {
                $res_flag = $res_all['flag'] ?? '';
                $res_flag2 = $res_all['flag2']??'';
                $res_end = $res_all['end']??'';
                if($res_flag){
                    $item['bi_an_new_close'] = $item['close'];
                    $res_flag_arr = explode(',',$res_flag);
                    foreach ($res_flag_arr as $res_item){
                        if(isset($res_all[$res_item])){
                            $item[$res_item]=$res_all[$res_item];
                        }
                    }
                    if($res_flag2 == '1'){ // 最后一秒
                        $item['flag']='close,low,high,open';
                        $item['flag2']='2';
                    }
                    if ($res_end == '1') {
                        $item['flag']='open,low,high';
                        $item['end'] = '';
                    }

                    if ($res_flag2 == '2') { // 币安数据进来
                        $item['high'] = max([
                            $res_all['high'],
                            $item['close'],
                            $res_all['open'],
                            $item['high']
                        ]);

                        $item['low'] = min([
                            $res_all['low'],
                            $item['close'],
                            $res_all['open'],
                            $item['low']
                        ]);
                    }
                }
            }
            $this->app(ChannelRecordData::class)->addRecordDataAll($recordIdKey, $item);
            //增加秒级K线，用于延迟结算
            if ($t == '1m') {
                $recordIdKeySecond  = $recordIdKey .":".time();
                $this->app(ChannelRecordData::class)->adds($recordIdKeySecond, $item);
                $this->app(ChannelRecordData::class)->expire($recordIdKeySecond,86400);
            }

            return true;
        }catch (\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[采集K线]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return 100;
        }
    }

    // k线操作 - 预设
    public function create($param)
    {
        $second_id = $param['second_id']??'';
        $preTime = $param['trade_time']??'';//开空时间
        $market = $param['market']??'';//交易对
        $type = $param['period']??'1min';//周期
        $direct = $param['direct']??'';//方向
        $mode = $param['frequency']??"";//频率
        try {

            $order = $this->logic->create([
                'second_id'=>$second_id,
                'market' => $market,
                'frequency' => $mode,
                'direct' => $direct,
                'period' => $type,
                'trade_time' => $preTime
            ]);
            if(!$order){
                throw new \Exception('添加失败');
            }

            // 查订单表，拿到预设时间的上一分钟，上上分中的58s之间的价格
            $start_time = date('Y-m-d H:i:57', strtotime('-2 minute', strtotime($preTime)));
            $end_time = date('Y-m-d H:i:s', strtotime('-1 minute', strtotime($preTime)));
            $price_arr = $this->app(UserSecondService::class)->getQuery()->where('created_at', '>=', $start_time)->where('created_at', '<', $end_time)->where('market', $market)->pluck('price')->toarray();
            if ($price_arr) {
                if($direct == '1') { // 让涨
                    $first_price = max($price_arr);
                } else { // 让跌
                    $first_price = min($price_arr);
                }
            } else {
                $first_price = 0;
            }
            
            
            // 查询恶意用户数据
            // $block_time = date('Y-m-d H:i:01', strtotime($preTime));
            // $betch_malice = $this->app(UserSecondService::class)->is_betch_malice();
            // $block_price = 0;
            // if(count($betch_malice) > 0){
            //     $block_price_arr = $this->app(UserSecondService::class)->getQuery()->where('created_at', $block_time)->where('market', $market)->whereIn('user_id',$betch_malice)->pluck('price')->toarray();
            //     if ($block_price_arr) {
            //         if($direct == '1') { // 让涨
            //             $block_price = min($block_price_arr);
            //         } else { // 让跌
            //             $block_price = max($block_price_arr);
            //         }
            //     }
            // }
           
            
            $error['msgs'][] ='marketTick edit 控K参数，预设时间：' . $preTime . '，币种：' . $market . '，频率：' . $mode . '，方向 : ' . $direct . '，周期：' . $type;
            $error['msgs'][] = '下单价格数组：' . json_encode($price_arr) . '，拿到的价格：' . $first_price;
            $this->logger('[控制K线]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            $pre_time = strtotime($preTime); // pre_time：2023-05-12 18:56:02，预设时间字符串转时间戳（秒）
            // 做预设时间及前18秒数据，一共19条数据
            $result = [];
            $length = 18;
            $append_length = -5;
            $open = '';// 预设所在分钟的最后一秒的收盘价是下一分钟的开盘价
            $x = 0;
            $this->app(SecondKline::class)->lastclear();
            for($i = $length; $i >= $append_length; $i--) { // 前N秒 + length-1（预设时间本身）
                $data = [];
                $data['pre_time'] = $pre_time;
                $tempTime = bcsub(strval($pre_time), $i,0);
                $time = strtotime(date('Y-m-d H:i:00', $tempTime));
                $data['time'] = $time;
                $data['market'] = $market;
                $data['type'] = $type;
                $data['execute_time'] = bcsub(strval($pre_time),$i+1,0);  // 执行时间，每条数据提前1s写
                $data['index'] = $length - $i;
                $data['for_index'] = $i;
                //第一条数据标记
                if ($i == $length){
                    $data['first'] = '1';
                    $data['mode'] = $mode;
                    $data['close'] = $first_price;
                }
                // 标记控K的最后一条数据，不算后加的5s
                if($i == 0) {
                    $data['flag2'] = '1';
                }
                // 标记整个控K的最后一条数据，算后加的5s
                if ($i == $append_length) {
                    $data['end'] = '1';
                }
                // 处理最后一秒的收盘价是下一整分钟的开盘价
                if($open) {
                    $data['open'] = $open;
                    $data['is_open'] = 'open';
                    $open = '';
                }
                $x++;
                if($direct == '1') { // 让涨
                    $data['flag'] = '1';    // 要处理，且标识redis不覆盖此数据
                } else { // 让跌
                    $data['flag'] = '2';    // 要处理，且标识redis不覆盖此数据
                }
                $pre_date_end_time = strtotime(date('Y-m-d H:i:59', $tempTime)); // 预设的每秒所在分钟的最后一秒
                if (intval($tempTime) == $pre_date_end_time) {
                    $open = '1';
                }
                if ($data['execute_time'] == strtotime(date('Y-m-d H:i:0', $pre_time))) {//整分钟数据
                    $data['one'] = '1';
                }
                // 加入有序集合
                $this->app(SecondKline::class)->lastmarket($market);
                $this->app(SecondKline::class)->addRecordId("{$market}",intval($data['execute_time']),intval($data['execute_time']));
                $hash_key = "{$market}:{$data['execute_time']}";
                $this->app(SecondKlineData::class)->addRecordDataAll($hash_key,$data);
            }

            return true;
        } catch (\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[控制K线]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }
    // k线操作 - 删除
    public function remove($id)
    {
        $info = $this->logic->find($id);
        if(!$info){
            return false;
        }
        $this->app(SecondKline::class)->lastclear();
        $this->app(SecondKline::class)->delRecord("{$info->market}");
        $pre_time = strtotime( $info->trade_time);
        $length = 18;
        $append_length = -5;
        for ($i = $length; $i >= $append_length; $i--) {
            $execute_time = bcsub(strval($pre_time),$i+1,0);  // 执行时间，每条数据提前1s写
            $hash_key = "{$info->market}:{$execute_time}";
            $this->app(SecondKlineData::class)->delRecordData($hash_key);
        }
        $this->logic->remove($id);
        return true;

    }

    /**
     * K线操作-重置
     * 1、拿到币安K线数据
     * 2、初始化数据第一条初始数据，
     * 3、控涨以第一条数据为基础一秒一次涨跌，最终要涨势、否则相反
     * 4、44秒开始----52秒拉平----涨跌-要涨势----58秒下单----肯定涨---------03秒结束下单总计19条数据------开始回归币安数据
     */
    // k线操作 - 写入
    public function bsettle($market,$time){//$time预设每秒得K线时间节点

        $redis = $this->getRedis();
        try {
            $hash_key = "{$market}:{$time}";
            $hash_key_name = $this->app(SecondKlineData::class)->getRecordDataName($hash_key);
            $kline_pre_set_processed = $this->app(SecondKlineData::class)->all($hash_key); // 待执行的数据
            if(!isset($kline_pre_set_processed['market'])){
                $this->logger('[控制K线]','kline')->info("{$hash_key_name}未设置该时间");
                return;
            }
            $this->logger('[控制K线]','kline')->info("开始执行了... ...要执行的数据：{$hash_key_name}" . json_encode($kline_pre_set_processed,JSON_UNESCAPED_UNICODE));
            // 当前时间所在整分钟的K线
            $current_whole_minute = strtotime(date('Y-m-d H:i:00', $time));
            $current_whole_minute_key =  "{$kline_pre_set_processed['market']}:{$kline_pre_set_processed['type']}:". $current_whole_minute;
            $current_whole_minute_key_name =  $this->app(ChannelRecordData::class)->getRecordDataName($current_whole_minute_key);
            $current_whole_minute_kline_data = $this->app(ChannelRecordData::class)->all($current_whole_minute_key);
            $this->logger('[控制K线]','kline')->info("读到当前整分钟K线数据{$current_whole_minute_key_name}：" . json_encode($current_whole_minute_kline_data,JSON_UNESCAPED_UNICODE));
            if (!$current_whole_minute_kline_data) {
                $current_whole_minute = strtotime(date('Y-m-d H:i:00', $time-60));
                $current_whole_minute_key =  "{$kline_pre_set_processed['market']}:{$kline_pre_set_processed['type']}:". $current_whole_minute;
                $current_whole_minute_key_name =  $this->app(ChannelRecordData::class)->getRecordDataName($current_whole_minute_key);
                $current_whole_minute_kline_data = $this->app(ChannelRecordData::class)->all($current_whole_minute_key);
                $this->logger('[控制K线]','kline')->info("当前分钟线的价格没读到,读到上一分钟的K线数据{$current_whole_minute_key_name}：" . json_encode($current_whole_minute_kline_data,JSON_UNESCAPED_UNICODE));
            }

            $is_open = $kline_pre_set_processed['is_open']??'';
            // 计算
            $first = $kline_pre_set_processed['first']??''; // 第一次的价格
            $mode = $kline_pre_set_processed['mode']??''; // 频率
            $first_length = $kline_pre_set_processed['length']??''; // 团队爆破用，标识最后一个
            // 第一次价格将要存到redis里的key
            $result_key = 'first:market:'.$kline_pre_set_processed['market'].':kline:'.strtolower($kline_pre_set_processed['type']) . ":" . $kline_pre_set_processed['pre_time'];
            $if_key = 'first:key:market:'.$kline_pre_set_processed['market'].':kline:'.strtolower($kline_pre_set_processed['type']) . ":" . $kline_pre_set_processed['pre_time'];
            $for_index = $kline_pre_set_processed['for_index']??0; // 普通控K的每条数据下标，用来执行后5s拉回逻辑，爆破默认都是1
            $current_whole_minute_kline_close_price = $current_whole_minute_kline_data['close']; // 当前整分钟的收盘价
            $current_whole_minute_kline_flag_val = $current_whole_minute_kline_data['flag']??''; // 当前整分钟的flag字段
            $num_cha = '';
            $flag = $kline_pre_set_processed['flag']??'';
            $flag2 = $current_whole_minute_kline_data['flag2']??'';
            if ($for_index > -1) { // 正常控K阶段，不算后5s
                if ($first) { // 只有第一条控K数据里会存初始价格，后续要用从redis里拿
                    // 拿到差值
                    $current_close = $current_whole_minute_kline_data['close']??'';
                    $first_price = $kline_pre_set_processed['close'];   // 拿到预设第一条数据中所存储的下单时间里最低价或最高价
                    if ($first_price > 0) { // 如果有值
                        $current_cha = abs(bcsub($first_price, $current_close, 6));
                        $init_price = $first_price;
                    } else {
                        $current_open = $current_whole_minute_kline_data['open']??'';
                        $current_cha = abs(bcsub($current_open, $current_close, 6));
                        $init_price = $current_open;
                    }

                    $this->logger('[控制K线]','kline')->info('第一次的价格：' . $first_price . '，差值：' . $current_cha);
                    // 得到每条控K将要加或减去的值
                    $count = $this->app(SecondKline::class)->getMemberScore($kline_pre_set_processed['market'], $time); // 本次控K的总数量
                    $num = 5; // 第5条数据拉平
                    $arr = $this->getRand($current_cha, $count-$num, $num, $kline_pre_set_processed['market'], $first, $current_whole_minute_kline_close_price, $first_price, $flag, $first_length, $mode);
                    $this->logger('[控制K线]','kline')->info('000000000000000000111111');
                    // 将每条控K将要加或减去的数组值、拉平位数、差值、第一次价格值等数据存入redis
                    $redis->set($result_key, json_encode($arr));
                    $num_cha = ['num'=>$num, 'current_cha'=>$current_cha,'init_price'=>$init_price];
                    $redis->set($if_key, json_encode($num_cha));

                    if ($first == '2' && $flag2) { // 定向爆破用
                        $data['flag2'] = '1';
                    }
                } else {
                    $this->logger('[控制K线]','kline')->info('000000000000000000222222');
                    $result_key_flag = $redis->get($result_key);
                    $if_key_flag = $redis->get($if_key);
                    if (!$result_key_flag || !$if_key_flag) {
                        $this->logger('[控制K线]','kline')->info('00000000000000000044444444');
                        return;
                    }
                    $arr = json_decode($result_key_flag, true);
                    $num_cha = json_decode($if_key_flag, true);
                }
                $this->logger('[控制K线]','kline')->info('00000000000000000000000000000000000000000000000');
            }else {
                $this->logger('[控制K线]','kline')->info('111111111111111111111111111111111111111111111111');
                $arr = [];
            }
            $index = $kline_pre_set_processed['index']??0;

            // 追加的5s
            if ($for_index < 0) {
                $this->logger('[控制K线]','kline')->info('222222222222222222222222222222222222222222222222222222');
                $append_first = 'append:first:market:'.$kline_pre_set_processed['market'].':kline:'.strtolower($kline_pre_set_processed['type']) . ":" . $kline_pre_set_processed['pre_time'];
                $bi_an_new_close = $current_whole_minute_kline_data['bi_an_new_close']??'';
                if (!$bi_an_new_close) {
                    $bi_an_new_close = $current_whole_minute_kline_close_price;
                }
                $this->logger('[控制K线]','kline')->info('开始追加的5s, 最后一条干预的close价格：'.$current_whole_minute_kline_close_price.'，币安过来的价格：'.$bi_an_new_close);
                if ($for_index == -1) {
                    $this->logger('[控制K线]','kline')->info('追加的5s的第一条数据，index为:'.$index);
                    $count = 5;
                    $current_cha = abs(bcsub($bi_an_new_close, $current_whole_minute_kline_close_price, 6));
                    $arr = $this->getRand($current_cha, $count, -1, $kline_pre_set_processed['market'], '', '', '', '', '');
                    // 把返回的数据结果存入redis
                    $redis->set($append_first, json_encode($arr));
                } else {
                    $this->logger('[控制K线]','kline')->info('开始追加的5s的其它数据，index为:'.$index);
                    $arr = json_decode($redis->get($append_first), true);
                }

                $this->logger('[控制K线]','kline')->info('最后控K的close价：'.$current_whole_minute_kline_close_price.'，币安过来的数据:'.$bi_an_new_close);
                if ($bi_an_new_close > $current_whole_minute_kline_close_price) {
                    $kline_pre_set_processed['flag'] = '1';
                } else {
                    $kline_pre_set_processed['flag'] = '2';
                }

                $append_index = abs($for_index) - 1;
                $coefficient = $this->cus_floatval($arr[$append_index], 6);
                $coefficient = $this->cus_floatval($coefficient, 6);
                $this->logger('[控制K线]','kline')->info('追加的5s数据，index为:'.$index.'，转换后数组的下标为：'.$append_index.'，将要加上的系数:'.$coefficient);
            } else {
                $coefficient = $this->cus_floatval($arr[$index], 6);
                $coefficient = $this->cus_strval($coefficient, 6);
                $this->logger('[控制K线]','kline')->info('当前整分钟K线close价格:'.$current_whole_minute_kline_close_price.'，将要加上的系数:'.$coefficient);
            }

            $flag = $kline_pre_set_processed['flag']??'';//控涨或跌
            $high = $current_whole_minute_kline_data['high']??'';
            $low = $current_whole_minute_kline_data['low']??'';
            $market_type = strtolower($kline_pre_set_processed['market']);
            $this->logger('[控制K线]','kline')->info('控涨、控跌前再次输出kline_pre_set_processed的数据：' . json_encode($kline_pre_set_processed));
            $is_explode = $kline_pre_set_processed['is_explode']??'';
            if ($flag == '1') {  // 让涨
                if ($market_type == 'xrpusdt') {
                    $float_num = 4;
                } elseif($market_type == 'dogeusdt' || $market_type == 'trxusdt') {
                    $float_num = 5;
                } elseif($market_type == 'ltcusdt' || $market_type == 'bnbusdt' || $market_type == 'ethusdt' || $market_type == 'solusdt') {
                    $float_num = 2;
                } else {
                    $float_num = 1;
                }
                // 定向爆破
                $is_malice = $kline_pre_set_processed['is_malice']??'';
                $blast_price = $kline_pre_set_processed['blast_price']??'';
                if ($is_malice && $blast_price) {
                    $this->logger('[控制K线]','kline')->info('定向爆破，当前价格：' . $blast_price);
                    if ($market_type == 'xrpusdt') {
                        $blast_coefficient = 0.0001;
                    } elseif($market_type == 'dogeusdt' || $market_type == 'trxusdt') {
                        $blast_coefficient = 0.00001;
                    } elseif($market_type == 'ltcusdt' || $market_type == 'bnbusdt' || $market_type == 'ethusdt' || $market_type == 'solusdt') {
                        $blast_coefficient = 0.01;
                    } else {
                        $blast_coefficient = 0.4;
                    }
                    $result = bcsub(strval($blast_price), strval($blast_coefficient), $float_num);
                    $this->logger('[控制K线]','kline')->info('控涨期间，定向爆破，计算后的价格：' . $result);
                } else {
                    $result = bcadd(strval($current_whole_minute_kline_close_price), strval($coefficient), $float_num);
                }

                $this->logger('[控制K线]','kline')->info('$current_whole_minute_kline_close_price：' . strval($current_whole_minute_kline_close_price));
                $this->logger('[控制K线]','kline')->info('$coefficient：' . strval($coefficient));
                $this->logger('[控制K线]','kline')->info('计算：' . bcadd(strval($current_whole_minute_kline_close_price), strval($coefficient), 6));
                $this->logger('[控制K线]','kline')->info('结算后的$result：' . $result);
                if($num_cha && $num_cha['num'] == $index+1 && !$is_explode){
                    $this->logger('[控制K线]','kline')->info('$index进来了：' . $index);
                    $real_price = $result; //bcadd(strval($num_cha['init_price']), strval($num_cha['current_cha']), $float_num);//初始值  +  差值 == 最总需要平的值
                }else{
                    $this->logger('[控制K线]','kline')->info('不在$index进来了：' . $index);
                    $real_price=$result;
                }

                if ($num_cha && $num_cha['num'] == $index+1 && $result <= $num_cha['init_price']) {
                    if($result < $real_price){
                        $result = $num_cha['init_price'];
                    } else {
                        $result = $real_price;
                    }
                    $this->logger('[控制K线]','kline')->info('====：$result' . $result);
                    $this->logger('[控制K线]','kline')->info('====：$real_price' . $real_price);
                }
                $this->logger('[控制K线]','kline')->info('$result:'.$result);
                $kline_pre_set_processed['close'] = $result;    // 将计算后的结果赋值给收盘价

                if ($for_index < 0) {
                    $kline_pre_set_processed['flag'] = 'close,low,high,open';
                } else {
                    $this->logger('[控制K线]','kline')->info('1111111111111：' . $current_whole_minute_kline_flag_val);
                    if ($current_whole_minute_kline_flag_val == 'open,low,high') {
                        $kline_pre_set_processed['flag'] = 'close,low,high,open';
                    } else {
                        $kline_pre_set_processed['flag'] = 'close,high,low';
                    }
                    if (floatval($result) > floatval($high)) {//修改最高价
                        $kline_pre_set_processed['high'] = $result;
                    }
                }
            }
            if ($flag == '2') {  // 让跌
                if ($market_type == 'xrpusdt') {
                    $float_num = 4;
                } elseif($market_type == 'dogeusdt' || $market_type == 'trxusdt') {
                    $float_num = 5;
                } elseif($market_type == 'ltcusdt' || $market_type == 'bnbusdt' || $market_type == 'ethusdt' || $market_type == 'solusdt') {
                    $float_num = 2;
                } else {
                    $float_num = 1;
                }
                // 定向爆破
                $is_malice = $kline_pre_set_processed['is_malice']??'';
                $blast_price = $kline_pre_set_processed['blast_price']??'';
                if ($is_malice && $blast_price) {
                    $this->logger('[控制K线]','kline')->info('定向爆破，当前价格：' . $blast_price);
                    if ($market_type == 'xrpusdt') {
                        $blast_coefficient = 0.0001;
                    } elseif($market_type == 'dogeusdt' || $market_type == 'trxusdt') {
                        $blast_coefficient = 0.00001;
                    } elseif($market_type == 'ltcusdt' || $market_type == 'bnbusdt' || $market_type == 'ethusdt' || $market_type == 'solusdt') {
                        $blast_coefficient = 0.01;
                    } else {
                        $blast_coefficient = 0.4;
                    }
                    $result = bcadd(strval($blast_price), strval($blast_coefficient), $float_num);
                    $this->logger('[控制K线]','kline')->info('控跌期间，定向爆破，计算后的价格：' . $result);
                } else {
                    $result = bcsub(strval($current_whole_minute_kline_close_price), strval($coefficient), $float_num);
                }

                $this->logger('[控制K线]','kline')->info('$current_whole_minute_kline_close_price：' . strval($current_whole_minute_kline_close_price));
                $this->logger('[控制K线]','kline')->info('$coefficient：' . strval($coefficient));
                $this->logger('[控制K线]','kline')->info('计算：' . bcsub(strval($current_whole_minute_kline_close_price), strval($coefficient), 6));
                $this->logger('[控制K线]','kline')->info('结算后的$result：' . $result);
                if($num_cha && $num_cha['num'] == $index+1 && !$is_explode){
                    $this->logger('[控制K线]','kline')->info('$index进来了：' . $index);
                    $real_price = bcsub(strval($num_cha['init_price']), strval($num_cha['current_cha']), $float_num);//初始值  -  差值 == 最总需要平的值
                }else{
                    $this->logger('[控制K线]','kline')->info('不在$index进来了：' . $index);
                    $real_price=$result;
                }

                if ($num_cha && $num_cha['num'] == $index+1 && $result >= $num_cha['init_price']) {
                    if($result > $real_price){
                        $result = $num_cha['init_price'];
                    } else {
                        $result = $real_price;
                    }
                    $this->logger('[控制K线]','kline')->info('====：$result' . $result);
                    $this->logger('[控制K线]','kline')->info('====：$real_price' . $real_price);
                }

                $this->logger('[控制K线]','kline')->info('$result:'.$result);
                $kline_pre_set_processed['close'] = $result;
                if ($for_index < 0) {
                    $kline_pre_set_processed['flag'] = 'close,low,high,open';
                } else {
                    $this->logger('[控制K线]','kline')->info('2222222222222：' . $current_whole_minute_kline_flag_val);
                    if ($current_whole_minute_kline_flag_val == 'open,low,high') {
                        $kline_pre_set_processed['flag'] = 'close,low,high,open';
                    } else {
                        $kline_pre_set_processed['flag'] = 'close,high,low';
                    }
                    if (floatval($result) < floatval($low)) {//修改最低价
                        $kline_pre_set_processed['low'] = $result;
                    }
                }
            }

            if ($is_open) { // 如果是最后一秒
                $kline_pre_set_processed['open'] = $current_whole_minute_kline_data['close'];    // 最后一秒的收盘价是下一分钟的开盘价
                $kline_pre_set_processed['low'] = $kline_pre_set_processed['open'];
                $kline_pre_set_processed['high'] = $kline_pre_set_processed['open'];
                $kline_pre_set_processed['flag'] = 'close,low,high,open'; // 标记锁死open
                if ($kline_pre_set_processed['close'] > $kline_pre_set_processed['high']) {
                    $kline_pre_set_processed['high'] = $kline_pre_set_processed['close'];
                }
                if ($kline_pre_set_processed['close'] < $kline_pre_set_processed['low']) {
                    $kline_pre_set_processed['low'] = $kline_pre_set_processed['close'];
                }
                unset($kline_pre_set_processed['is_open']); // 移除脏数据
            } else {
                $current_flag_val = $current_whole_minute_kline_data['flag']??'';   // 拿到当前整分钟的flag，主要是为了处理下一分钟后的那几秒
                $flag_temp = $kline_pre_set_processed['flag']??'';  // 拿到待处理数据当前的flag值，因为一开始是标记涨（1）或者跌（2）
                if ($flag_temp != 'close,high,low' && $flag_temp != 'close,open' && $flag_temp != 'close,high' && $flag_temp != 'close,low' && $current_flag_val != 'close,low,high,open' && $current_flag_val != 'open,low,high') {
                    $kline_pre_set_processed['flag'] = 'close';
                } else {
                    if ($current_flag_val == 'close,open') {
                        $kline_pre_set_processed['flag'] = $flag_temp . ',open';
                    } else if ($current_flag_val == 'close,low,high,open' || $current_flag_val == 'open,low,high') {
                        if ($kline_pre_set_processed['close'] > $current_whole_minute_kline_data['high']) {
                            $kline_pre_set_processed['high'] = $kline_pre_set_processed['close'];
                        }
                        if ($kline_pre_set_processed['close'] < $current_whole_minute_kline_data['low']) {
                            $kline_pre_set_processed['low'] = $kline_pre_set_processed['close'];
                        }
                        // $kline_pre_set_processed['flag'] = $current_flag_val;
                        if ($current_flag_val != 'open,low,high') {
                            $kline_pre_set_processed['flag'] = $current_flag_val;
                        }
                    } else {
                        $kline_pre_set_processed['flag'] = $flag_temp;
                    }
                }
            }
            $this->logger('[控制K线]','kline')->info('计算后的数据：' . json_encode($kline_pre_set_processed,JSON_UNESCAPED_UNICODE));
            $one = $kline_pre_set_processed['one']??'';
            if ($one) {
                // 如果是下一整分钟，就得写入zadd排序
                $recordId = "{$kline_pre_set_processed['market']}:".strtolower($kline_pre_set_processed['type']);
                $z_add_time = $kline_pre_set_processed['time'];
                $this->app(ChannelRecord::class)->addRecordId($recordId,$z_add_time,(string)$z_add_time);
            }
            $kline_pre_set_processed['ts'] = $time * 1000;
            $recordIdKey =  "{$kline_pre_set_processed['market']}:{$kline_pre_set_processed['type']}:{$kline_pre_set_processed['time']}";
            $this->app(ChannelRecordData::class)->addRecordDataAll($recordIdKey, $kline_pre_set_processed);
            // 从待执行的有序集合和hash中移除数据
            $this->app(SecondKlineData::class)->delRecordData($hash_key);
        }catch (\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[控制K线]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /*k线操作 - 拉平计算*/
    private function getRand($angle=100, $n2=2, $n=6, $market='', $first='', $current_whole_minute_kline_close_price='', $first_price='', $flag='', $first_length='', $mode='') {
        $this->logger('[控制K线]','kline')->info('$angle = '.$angle.'，$n2 = '.$n2.', $n = '.$n.', $market = '. $market);
        $this->logger('[控制K线]','kline')->info('$current_whole_minute_kline_close_price = '.$current_whole_minute_kline_close_price);
        $this->logger('[控制K线]','kline')->info('$first_price = '.$first_price.'，$flag = '.$flag.', $first_length = '.$first_length);
        $arr = array();
        $temp = 100000000000;
        $temp_arr_start = array();  // 拉平前
        $temp_arr_end = array();    // 拉平后
        // 正常控K阶段
        if ($n != -1) {
            /************* 拉平操作 *************/
            if($angle==0){  // 差值为0
                if ($market == 'btcusdt') {  // 1位小数，盘差 > 10
                    $arr = [0.2, 0.1, 0, 0, -0.02];
                } elseif($market == 'ethusdt') { // 2位小数，盘差 = 1
                    $arr = [0.03, 0.04, 0, 0, -0.02];
                } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') { // 2位小数，盘差 = 0.2
                    $arr = [0.02, 0, 0, 0.03, -0.01];
                } elseif($market == 'xrpusdt') { // 4位小数，盘差 = 0.002
                    $arr = [0.0002, 0, 0, 0.0001, -0.0002];
                } else { // dogeusdt，5位小数，盘差 = 0.0003
                    $arr = [0.00001, 0, 0.00001, -0.00001, 0];
                }
            } else {
                $current_whole_minute_kline_close_price = $this->cus_floatval($current_whole_minute_kline_close_price);
                $first_price = $this->cus_floatval($first_price);
                // 普通控K
                if ($first != '2' && $first != '3') {
                    if ($flag == '1') { //控涨
                        if ($current_whole_minute_kline_close_price <= $first_price) {
                            if ($mode == '2') { // 低频
                                $this->logger('[控制K线]','kline')->info('低频模式');
                                for($i=1;$i<$n;$i++){
                                    $arr[]=0;
                                }
                                $arr[] = $angle;
                                shuffle($arr); // 摇一下
                            } else { // 标准
                                $this->logger('[控制K线]','kline')->info('标准模式');
                                $avg=$angle/$n; // 得到拉平前5s的平均值

                                for($i=1;$i<$n;$i++){
                                    $this->logger('[控制K线]','kline')->info($avg/2*$temp . '----' . $avg*$temp);
                                    $arr[]=mt_rand(intval($avg/2*$temp),intval($avg*$temp)) / $temp;
                                }

                                // 第5s
                                $arr[]=$angle-array_sum($arr);
                            }
                        } else {
                            for($i=1;$i<$n;$i++){
                                $arr[]=0;
                            }
                            if ($market == 'btcusdt') {
                                $arr[] = 0.1;
                            } elseif($market == 'ethusdt') { // 2位小数，盘差 = 1
                                $arr[] = 0.03;
                            } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') { // 2位小数，盘差 = 0.2
                                $arr[] = 0.02;
                            } elseif($market == 'xrpusdt') { // 4位小数，盘差 = 0.002
                                $arr[] = 0.0001;
                            } else { // dogeusdt，5位小数，盘差 = 0.0003
                                $arr[] = 0.00001;
                            }
                            shuffle($arr); // 摇一下
                        }
                    } else { //控跌
                        if ($current_whole_minute_kline_close_price >= $first_price) {
                            if ($mode == '2') { // 低频
                                $this->logger('[控制K线]','kline')->info('低频模式');
                                for($i=1;$i<$n;$i++){
                                    $arr[]=0;
                                }
                                $arr[] = $angle;
                                shuffle($arr); // 摇一下
                            } else { // 标准
                                $this->logger('[控制K线]','kline')->info('标准模式');
                                $avg=$angle/$n; // 得到拉平前5s的平均值

                                for($i=1;$i<$n;$i++){
                                    $this->logger('[控制K线]','kline')->info($avg/2*$temp . '----' . $avg*$temp);
                                    $arr[]=mt_rand(intval($avg/2*$temp),intval($avg*$temp)) / $temp;
                                }

                                // 第5s
                                $arr[]=$angle-array_sum($arr);
                            }
                        } else {
                            for($i=1;$i<$n;$i++){
                                $arr[]=0;
                            }
                            if ($market == 'btcusdt') {
                                $arr[] = 0.1;
                            } elseif($market == 'ethusdt') { // 2位小数，盘差 = 1
                                $arr[] = 0.03;
                            } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') { // 2位小数，盘差 = 0.2
                                $arr[] = 0.02;
                            } elseif($market == 'xrpusdt') { // 4位小数，盘差 = 0.002
                                $arr[] = 0.0001;
                            } else { // dogeusdt，5位小数，盘差 = 0.0003
                                $arr[] = 0.00001;
                            }
                            shuffle($arr); // 摇一下
                        }
                    }

                } else {
                    if ($first == '3') { // 团队爆破
                        for($i = 0; $i < $first_length; $i++) {
                            $arr[] = 0;
                        }
                        if ($flag == '1') {
                            if ($current_whole_minute_kline_close_price <= $first_price) {
                                $temp_first_price = 0;
                                if ($market == 'btcusdt' || $market == 'ethusdt') {
                                    $temp_first_price += 0.1;
                                } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                                    $temp_first_price += 0.01;
                                } elseif($market == 'xrpusdt') {
                                    $temp_first_price += 0.0001;
                                } else {
                                    $temp_first_price += 0.00001;
                                }
                                $temp_angle = $angle + $temp_first_price;
                                $arr[0] = $temp_angle;
                                if ($first_length >= 2) {
                                    $arr[1] = $temp_first_price;
                                }
                            }
                        } else {
                            if ($current_whole_minute_kline_close_price >= $first_price) {
                                $temp_first_price = 0;
                                if ($market == 'btcusdt' || $market == 'ethusdt') {
                                    $temp_first_price += 0.1;
                                } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                                    $temp_first_price += 0.01;
                                } elseif($market == 'xrpusdt') {
                                    $temp_first_price += 0.0001;
                                } else {
                                    $temp_first_price += 0.00001;
                                }
                                $temp_angle = $angle + $temp_first_price;
                                $arr[0] = $temp_angle;
                                if ($first_length >= 2) {
                                    $arr[1] = $temp_first_price;
                                }
                            }
                        }
                    } else { // 金额爆破
                        if ($flag == '1') {
                            if ($current_whole_minute_kline_close_price > $first_price) {
                                $arr = [0, 0, 0, 0, 0];
                            } else {
                                $temp_first_price = 0;
                                if ($market == 'btcusdt' || $market == 'ethusdt') {
                                    $temp_first_price += 0.1;
                                } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                                    $temp_first_price += 0.01;
                                } elseif($market == 'xrpusdt') {
                                    $temp_first_price += 0.0001;
                                } else {
                                    $temp_first_price += 0.00001;
                                }
                                $temp_angle = $angle + $temp_first_price;
                                $arr = [$temp_angle, $temp_first_price, 0, 0, 0];
                            }
                        } else {
                            if ($current_whole_minute_kline_close_price < $first_price) {
                                $arr = [0, 0, 0, 0, 0];
                            } else {
                                $temp_first_price = 0;
                                if ($market == 'btcusdt' || $market == 'ethusdt') {
                                    $temp_first_price += 0.1;
                                } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                                    $temp_first_price += 0.01;
                                } elseif($market == 'xrpusdt') {
                                    $temp_first_price += 0.0001;
                                } else {
                                    $temp_first_price += 0.00001;
                                }
                                $temp_angle = $angle + $temp_first_price;
                                $arr = [$temp_angle, $temp_first_price, 0, 0, 0];
                            }
                        }
                    }

                }

            }

            /************* 拉平后操作 *************/
            if ($market == 'btcusdt') {  // 1位小数，盘差 > 10
                $decimal_place = 10;
            } elseif($market == 'ethusdt' || $market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                // ethusdt：2位小数，盘差 = 1。ltcusdt、bnbusdt：2位小数，盘差 = 0.2
                $decimal_place = 100;
            } elseif($market == 'xrpusdt') { // 4位小数，盘差 = 0.002
                $decimal_place = 10000;
            } else { // dogeusdt，5位小数，盘差 = 0.0003
                $decimal_place = 100000;
            }

            if ($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                $temp_arr_start[] = 1 / $decimal_place; // 51s
                $temp_arr_start[] = 0;
            } elseif($market == 'btcusdt') {
                $temp_arr_start[] = mt_rand(7,8) / $decimal_place; // 51s
                $temp_arr_start[] = mt_rand(7,8) / $decimal_place;
            } elseif($market == 'ethusdt') {
                $temp_arr_start[] = mt_rand(2,3) / $decimal_place; // 51s
                $temp_arr_start[] = mt_rand(2,3) / $decimal_place;
            } else {
                $temp_arr_start[] = mt_rand(1,2) / $decimal_place; // 51s
                $temp_arr_start[] = mt_rand(1,2) / $decimal_place;
            }
            $temp1 = mt_rand(1,2);
            $temp3 = mt_rand(1,2);
            if ($temp1 == 1) {
                $temp2 = 2;
            } else {
                $temp2 = 1;
            }
            if ($temp3 == 1) {
                $temp4 = 2;
            } else {
                $temp4 = 1;
            }
            if ($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') {
                $temp_arr_base = [0, 2, 0, 3, 0, 0, 3, 0, -2, 0, 2, 0];
            } elseif($market == 'btcusdt') {
                $temp_arr_base = [25, -9, 0, 16, -7, 0, 27, 18, 0, 0, 10, -10];
            } elseif($market == 'ethusdt') {
                $temp_arr_base = [23, -12, 0, 29, -12, 0, -5, 26, 0, 0, 18, -12];
            } else {
                $temp_arr_base = [$temp1, -1, 0, $temp2, -2, 0, $temp3, -1, 0, 0, $temp4, -1];
            }
            $length = count($temp_arr_base);
            for ($i = 0; $i < $length; $i++) {
                $temp_arr_end[] = $temp_arr_base[$i] / $decimal_place;
            }

            $temp_arr = array_merge($temp_arr_start, $temp_arr_end);
            $arr = array_merge($arr, $temp_arr);
        } else {    // 后加的5s，拉回
            if ($angle==0) {
                if ($market == 'btcusdt') {  // 1位小数，盘差 > 10
                    $angle = 2;
                } elseif($market == 'ethusdt') { // 2位小数，盘差 = 1
                    $angle = 0.2;
                } elseif($market == 'ltcusdt' || $market == 'bnbusdt' || $market == 'solusdt') { // 2位小数，盘差 = 0.2
                    $angle = 0.1;
                } elseif($market == 'xrpusdt') { // 4位小数，盘差 = 0.002
                    $angle = 0.001;
                } else { // dogeusdt，5位小数，盘差 = 0.0003
                    $angle = 0.0001;
                }
            }
            $avg2 = $angle/$n2;
            $rand_temp_arr = $this->yang_numberRand(1, 5, 1);
            sort($rand_temp_arr);
            for($i=1; $i<=$n2; $i++){
                $x = mt_rand(intval($avg2/2*$temp),intval($avg2*$temp)) / $temp;
                // 震荡
                if (in_array($i, $rand_temp_arr)) {
                    $arr[] = $x * 0;
                } else {
                    $arr[] = $x;
                }
            }
        }
        $this->logger('[控制K线]','kline')->info('$arr:'.json_encode($arr));
        return $arr;
    }
    /*k线操作 - 拉平计算*/
    private function yang_numberRand($begin = 0, $end = 20, $limit = 5){

        $rand_array = range($begin, $end);

        shuffle($rand_array); //调用现成的数组随机排列函数

        return array_slice($rand_array, 0, $limit); //截取前$limit个

    }


    /* k线操作 - 爆破 - 金额- 赛选数据 */
    public function bsettle_amount($blasting_money_amount,$blasting_user_ids, $white_uids,$money_jian_kong_num){

        $order_arr = $this->app(UserSecondService::class)->getQuery()->where(['settle_status'=>0, 'is_kong'=>0])->where(function ($query) use ($blasting_money_amount,$blasting_user_ids){
            return $query->whereIn('user_id',$blasting_user_ids)->orWhere('num', '>=', $blasting_money_amount);
        })->select(['id', 'user_id', 'num', 'market', 'price', 'direct', 'should_settle_time', 'created_at'])->orderBy('num','desc')->get()->toArray();


        if($order_arr &&  count($order_arr) > 0){
            $this->logger('[控制K线]','kline')->info('有需要搞得订单:'.json_encode($order_arr));

            // 先看这些订单的应结算时间间隔，是否大5秒，如果每个之间都大于5秒，每个都爆，如果有小于5秒的，则看哪个金额大，爆谁
            $target_order_arr = [];
            foreach ($order_arr as $item) {
                if (in_array($item['user_id'], $white_uids)) { // 白名单
                    continue;
                }
                if (!$target_order_arr) { // 默认下单金额从大到小排序，所以第一个直接放到目标执行数组中
                    $target_order_arr[] = $item;
                } else {
                    // 判断当前数组的应结算时间和目标数组最后一条的应结算时间间隔
                    $current_obj_should_settle_times = $item['should_settle_time'];
                    $current_obj_should_settle_times_s = intval($current_obj_should_settle_times) / 1000;

                    $target_order_arr_last_info = $target_order_arr[count($target_order_arr) - 1]; // 拿到目标数组最后一条
                    $target_order_arr_last_info_should_settle_times = $target_order_arr_last_info['should_settle_time'];
                    $target_order_arr_last_info_should_settle_times_s = intval($target_order_arr_last_info_should_settle_times) / 1000;

                    $current_obj_market = $item['market'];
                    $target_order_arr_last_info_market = $target_order_arr_last_info['market'];

                    if  ($current_obj_market != $target_order_arr_last_info_market) {
                        $target_order_arr[] = $item;
                    }elseif (abs($current_obj_should_settle_times_s - $target_order_arr_last_info_should_settle_times_s) > 5) {
                        $target_order_arr[] = $item;
                    } else{
                        // 看哪个金额大放哪个
                        $current_obj_num = $item['num'];
                        $target_order_arr_last_info_num = $target_order_arr_last_info['num'];
                        if ($current_obj_num > $target_order_arr_last_info_num) { // 如果新的数据的下单金额大于目标数组最后一条，则替换
                            $target_order_arr[count($target_order_arr) - 1] = $item;
                        }
                    }
                }
            }

            // 执行爆破逻辑判断
            foreach ($target_order_arr as $item2) {
                $info = $item2;
                $target_order_should_settle_times = $info['should_settle_time'];
                $target_start_time = intval($target_order_should_settle_times / 1000);
                $price = $info['price']; // 订单买入价
                $market = $info['market'];
                $direct = $info['direct'];
                $target_user = $info['user_id'];
                //用户间接性爆破标识
                $is_jian_kong_key = date('Y-m-d') . "_money_" . $target_user;
                $is_jian_kong_order_key = date('Y-m-d') . "_order_not" . $target_user;
                $is_jian_kong_val = $this->getCache()->get($is_jian_kong_key);
                $is_jian_kong_order =  $this->getCache()->get($is_jian_kong_order_key);
                $is_jian_kong_order_arr = [];
                if($is_jian_kong_order){
                    $is_jian_kong_order_arr = json_decode($is_jian_kong_order,true);
                }
                if(in_array($info['id'],$is_jian_kong_order_arr)){
                    continue;
                }
                // 查是否和正常控K撞线
                $secondKline = $this->app(SysSecondKline::class)->getQuery()->orderBy('trade_time','desc')->first('trade_time');
                $pre_time =  $secondKline->trade_time; // 结束时间
                $this->logger('[控制K线]','kline')->info('判断是否和正常控K撞线:'. $secondKline->trade_time );
                $pre_time = strtotime($pre_time); // 结束时间
                $pre_time_before = bcsub(strval($pre_time),'23',0); // 正常控K开始时间，单位s  42
                $current_pre_time_before = bcsub(strval($target_start_time),'2',0); // 应结算将要控K的开始时间

                $this->logger('[控制K线]','kline')->info('判断是否和正常控K撞线');
                // 判断是否和正常控K撞线
                if ($current_pre_time_before < $pre_time_before || $current_pre_time_before > $pre_time) { // 没撞线
                    $this->logger('[控制K线]','kline')->info('没撞线');

                    // 拿到目标用户的应结算时间，判断，是否在56s~05s之间，如果不在，直接爆。如果在，再去看58s~03s应结算单量人数，如果够100，不爆，反之，爆他
                    $s = intval(date('s', intval($target_start_time)));
                    if ($s >= 56 || $s <= 5) {  // 在56s~05s之间
                        // 处理该订单应结算时间所在分钟的58s~01s 绕过不执行爆破
                        if($s >= 58 || $s <= 1){ continue; }
                        $this->logger('[控制K线]','kline')->info('金额爆破在56s~05s之间');
                        // 处理该订单应结算时间所在分钟的58s~03s
                        if ($s >= 0 && $s <= 5) {
                            $this->logger('[控制K线]','kline')->info('处理该订单应结算时间所在分钟的58s~03s  0-5');
                            $end = strtotime(date('Y-m-d H:i:03', intval($target_start_time)));
                            $start = bcsub(strval($end),'5',0);
                        } else {
                            $this->logger('[控制K线]','kline')->info('0-5???');
                            $start = strtotime(date('Y-m-d H:i:58', intval($target_start_time)));
                            $end = bcadd(strval($start),'5',0);
                        }
                        $start = intval($start) * 1000;
                        $end = intval($end) * 1000;

                        // 查看58s~03s应结算订单中有没有带单老师的订单
                        $userIds = explode('@',$this->app(SysConfigService::class)->value('blasting_team_ids'));
                        $order_count = $this->app(UserSecondService::class)->getQuery()->where(['settle_status'=>0])->where('should_settle_time','>=', $start)->where('should_settle_time', '<=', $end)->whereIn('user_id',$userIds)->count();
                        if ($order_count == 0) {

                            if ( 1 >= intval($money_jian_kong_num) ){
                                $this->app(UserSecondService::class)->getQuery()->where('id',$info['id'])->update(['is_kong' => 1]);
                                $this->logger('[控制K线]','kline')->info('（金额爆破）在56s~05s之间111111');
                                $this->blasting_amount($target_start_time, $price, $target_user, $market, $direct);
                            } else {
                                if(intval($is_jian_kong_val) % intval($money_jian_kong_num) != 0){ //第一次0必爆，第二次1，1%n = 1 肯定不爆，
                                    $this->logger('[控制K线]','kline')->info('（金额爆破）在56s~05s之间222222');
                                    $this->getCache()->set($is_jian_kong_key , intval($is_jian_kong_val)  + 1 , 86400);
                                    //记录本次不搞的订单-防止重复执行影响，$is_jian_kong_key的累加
                                    $is_jian_kong_order_arr[] = $info['id'];
                                    $this->getCache()->set($is_jian_kong_order_key , json_encode($is_jian_kong_order_arr), 86400);
                                    continue;
                                }
                                $this->app(UserSecondService::class)->getQuery()->where('id',$info['id'])->update(['is_kong' => 1]);
                                $this->logger('[控制K线]','kline')->info('（金额爆破）在56s~05s之间00000');
                                $this->blasting_amount($target_start_time, $price, $target_user, $market, $direct);
                            }
                            $this->getCache()->set($is_jian_kong_key , intval($is_jian_kong_val)  + 1 , 86400);
                        }
                    }else{

                        if ( 1 >= intval($money_jian_kong_num) ){
                            $this->app(UserSecondService::class)->getQuery()->where('id',$info['id'])->update(['is_kong' => 1]);
                            $this->logger('[控制K线]','kline')->info('（金额爆破）不在56s~05s之间1111111');
                            $this->blasting_amount($target_start_time, $price, $target_user, $market, $direct);
                        } else {
                            if(intval($is_jian_kong_val) % intval($money_jian_kong_num) != 0){ //第一次0必爆，第二次1，1%n = 1 肯定不爆，
                                $this->logger('[控制K线]','kline')->info('（金额爆破）不在56s~05s之间222222');
                                $this->getCache()->set($is_jian_kong_key , intval($is_jian_kong_val)  + 1 , 86400);
                                //记录本次不搞的订单-防止重复执行影响，$is_jian_kong_key的累加
                                $is_jian_kong_order_arr[] = $info['id'];
                                $this->getCache()->set($is_jian_kong_order_key , json_encode($is_jian_kong_order_arr), 86400);
                                continue;
                            }
                            $this->app(UserSecondService::class)->getQuery()->where('id',$info['id'])->update(['is_kong' => 1]);
                            $this->logger('[控制K线]','kline')->info('（金额爆破）不在56s~05s之间000000');
                            $this->blasting_amount($target_start_time, $price, $target_user, $market, $direct);
                        }
                        $this->getCache()->set($is_jian_kong_key , intval($is_jian_kong_val)  + 1 , 86400);
                    }
                }
            }
        }
    }

    /*k线操作 - 爆破- 金额 - 组织数据*/
    public function blasting_amount($target_start_time, $price, $target_user, $market, $direct){
        $second = $this->app(SysSecondService::class)->searchApi($market);
        if (!$second){return false;}
        $pre_time = $target_start_time;  // 该用户应结算时间
        try {
            $order = $this->logic->create([
                'second_id'=>$second['id'],
                'market' => $market,
                'frequency' => 1,
                'direct' => $direct,
                'period' => '1min',
                'trade_time' =>  date('Y-m-d H:i:s', intval($pre_time)),
                'is_malice' => 1 // 0控k  1个人定向爆破  2团队爆破
            ]);
            if(!$order){
                throw new \Exception('添加失败');
            }
            // 预设K线
            $first_price = $price;   // 买入价
            $length = 0;
            $open = '';     // 预设所在分钟的最后一秒的收盘价是下一分钟的开盘价
            $this->logger('[控制K线]','kline')->info(  '金额爆破 控K参数，预设时间：' .  $pre_time . '，币种：' . $market . '，频率：1' .  '，方向 : ' . $direct . '，周期：1min' . '，目标用户：' . $target_user);
            for($i = $length; $i >= -2; $i--) {
                $data = [];
                $data['pre_time'] = $pre_time;
                $tempTime = bcsub(strval($pre_time), strval($i),0);
                $time_temp = strtotime(date('Y-m-d H:i:00', intval($tempTime)));
                $data['time'] = $time_temp;
                $data['market'] = $market;
                $data['type'] = '1min';
                $data['execute_time'] = bcsub(strval($pre_time),strval($i+1),0);  // 执行时间，每条数据提前1s写
                $data['index'] = $length - $i;
                $data['for_index'] = 1;
                $data['is_explode'] = '1';

                if ($i == $length){
                    $data['first'] = '2';
                    $data['close'] = $first_price;
                }

                // 标记控K的最后一条数据，不算后加的5s
                if($i == -2) {
                    $data['flag2'] = '1';
                    $data['end'] = '1';
                }

                // 处理最后一秒的收盘价是下一整分钟的开盘价
                if($open) {
                    $data['open'] = $open;
                    $data['is_open'] = 'open';
                    $open = '';
                }

                if($direct == '1') { // 买的涨，控跌
                    $data['flag'] = '2';
                } else { // 买的跌，控涨
                    $data['flag'] = '1';
                }

                $pre_date_end_time = strtotime(date('Y-m-d H:i:59', intval($tempTime))); // 预设的每秒所在分钟的最后一秒
                if (intval($tempTime) == $pre_date_end_time) {
                    $open = '1';
                }

                if ($data['execute_time'] == strtotime(date('Y-m-d H:i:0', intval($pre_time)))) {
                    $data['one'] = '1';
                }

                // 加入有序集合
                $this->app(SecondKline::class)->lastmarket($market);
                $this->app(SecondKline::class)->addRecordId("{$market}",intval($data['execute_time']),intval($data['execute_time']));
                $hash_key = "{$market}:{$data['execute_time']}";
                $this->app(SecondKlineData::class)->addRecordDataAll($hash_key,$data);
            }

            return true;
        } catch (\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[控制K线]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }


    /*k线操作 - 爆破-团队*/
    public function blasting_team($target_start_time, $target_end_time, $price, $market, $direct){

        $second = $this->app(SysSecondService::class)->searchApi($market);
        if (!$second){return false;}
        $preTime = $target_start_time;  // 该用户应结算时间
        $char_lengt = $target_end_time - $target_start_time;
        try {
            $order = $this->logic->create([
                'second_id'=>$second['id'],
                'market' => $market,
                'frequency' => 1,
                'direct' => $direct,
                'period' => '1min',
                'trade_time' =>  date('Y-m-d H:i:s', intval($preTime)),
                'is_malice' => 2 // 0控k  1个人定向爆破  2团队爆破
            ]);
            if(!$order){
                throw new \Exception('添加失败');
            }
            // 预设K线
            $first_price = $price;   // 买入价
            $length = 1;
            $open = '';     //预设所在分钟的最后一秒的收盘价是下一分钟的开盘价
            $end_length = -($char_lengt+2)-1;
            $this->logger('[控制K线]','kline')->info(  '团队爆破 控K参数，预设时间：' .  $preTime . '，币种：' . $market . '，频率：1' .  '，方向 : ' . $direct . '，周期：1min');
            for($i = $length; $i >= $end_length; $i--) {
                $data = [];
                $data['pre_time'] = $preTime;
                $tempTime = bcsub(strval($preTime), strval($i),0);
                $time_temp = strtotime(date('Y-m-d H:i:00', intval($tempTime)));
                $data['time'] = $time_temp;
                $data['market'] = $market;
                $data['type'] = '1min';
                $data['execute_time'] = bcsub(strval($preTime),strval($i+1),0);  // 执行时间，每条数据提前1s写
                $data['index'] = $length - $i;
                $data['for_index'] = 1;
                $data['is_explode'] = '2';
                if ($i == $length){
                    $data['first'] = '3';
                    $data['length'] = abs($end_length) + 1;
                    $data['close'] = $first_price;
                }
                // 标记控K的最后一条数据，不算后加的5s
                if($i == $end_length) {
                    $data['flag2'] = '1';
                    $data['end'] = '1';
                }
                // 处理最后一秒的收盘价是下一整分钟的开盘价
                if($open) {
                    $data['open'] = $open;
                    $data['is_open'] = 'open';
                    $open = '';
                }
                if($direct == '1') { // 买的涨，控跌
                    $data['flag'] = '2';
                } else { // 买的跌，控涨
                    $data['flag'] = '1';
                }
                $pre_date_end_time = strtotime(date('Y-m-d H:i:59', intval($tempTime))); // 预设的每秒所在分钟的最后一秒
                if (intval($tempTime) == $pre_date_end_time) {
                    $open = '1';
                }
                if ($data['execute_time'] == strtotime(date('Y-m-d H:i:0', intval($preTime)))) {
                    $data['one'] = '1';
                }
                // 加入有序集合
                $this->app(SecondKline::class)->lastmarket($market);
                $this->app(SecondKline::class)->addRecordId("{$market}",intval($data['execute_time']),intval($data['execute_time']));
                $hash_key = "{$market}:{$data['execute_time']}";
                $this->app(SecondKlineData::class)->addRecordDataAll($hash_key,$data);
            }
            return true;
        } catch (\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[控制K线]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    // k线操作 - 写入-爆破-团队
    /**
     * 一组情况
     * 查当前持仓订单，同一币种、同一方向，同一下单时间，大于10个订单，且下单金额合计大于2000U，控结算
     * 1、订单表添加字段，标记是否控线，is_kong
     *
     * $max_count 团队下单量
     * $max_num 团队下单额
     * $userIds 排除用户
     */
    public function bsettle_team($max_count = 4,$max_num = 2000,$white_uids){
        $order_arr = $this->app(UserSecondService::class)->getQuery()->where(['settle_status'=>0, 'is_kong'=>0])->whereNotIn('user_id',$white_uids)
            ->select(['id', 'num', 'market', 'price', 'direct', 'should_settle_time', 'created_at'])->orderBy('created_at','desc')->get()->toArray();
        if($order_arr &&  count($order_arr)>=$max_count){
            $market_order_info_arr = [];
            $market_order_arr = [];   // 记录币种对应的订单号，如：btcusdt=>[1,2,3]
            $market_create_time_arr = [];  // 记录相同币种大于10单相同下单时间的
            $market_direct_arr = [];    // 记录相同币种-相同价格大于10单相同方向的
            $market_num_arr = [];       // 记录相同币种-相同价格-相同方向大于10单的下单总额
            $market_max_arr = [];

            // 1、根据币种，将订单分组
            foreach ($order_arr as &$item) {
                $item['created_at'] = strtotime($item['created_at']);
                if(!isset($market_order_arr[$item['market']])){
                    $market_order_arr[$item['market']] = [];
                }
                $market_order_arr[$item['market']][] = $item;
                $market_order_info_arr[$item['id']] = $item;
            }

            $this->logger('[控制K线]','kline')->info('将订单分组:'.json_encode($market_order_arr));
            $this->logger('[控制K线]','kline')->info('将订单分组:'.json_encode($market_order_info_arr));

            // 2、判断订单分组中，有没有同一币种大于10个订单的
            foreach ($market_order_arr as $key=>$item) {
                if (count($item) >= $max_count) {
                    $market_max_arr[] = $key;
                }
            }

            $this->logger('[控制K线]','kline')->info('有没有大于10单的:'.json_encode($market_max_arr));

            $createTime = [];
            $create_times_arr = [];
            // 3、判断大于等于10笔订单的下单币种对应订单的下单时间是否都相同（因为有范围所以差一两秒无所谓）
            foreach ($market_max_arr as $market) {
                foreach ($market_order_arr[$market] as $order) {
                    if(!isset($createTime[$market.'_'.$order['created_at']])){
                        $createTime[$market.'_'.$order['created_at']] = 0;
                        $create_times_arr[$market.'_'.$order['created_at']] = [];
                    }
                    $createTime[$market.'_'.$order['created_at']]++;
                    $create_times_arr[$market.'_'.$order['created_at']][] = $order['id'];
                }
            }

            $this->logger('[控制K线]','kline')->info('下单时间是否都相同:'.json_encode($createTime));
            $this->logger('[控制K线]','kline')->info('下单时间是否都相同:'.json_encode($create_times_arr));


            // 4、判断同一时间的有没有大于10个订单的
            foreach ($createTime as $key2=>$item2) {
                if ($item2 >= $max_count) {
                    $market_create_time_arr[] = $key2;
                }
            }
            $this->logger('[控制K线]','kline')->info('同一时间:'.json_encode($market_create_time_arr));

            $directs = [];
            $directs_ids = [];
            // 5、基于同一价格，找有没有同一方向的订单
            foreach ($market_create_time_arr as $item3) {
                [$market1,$create_time1]=explode('_', $item3);
                foreach ($market_order_arr[$market1] as $order) {
                    if($order['created_at'] == $create_time1){
                        if(!isset($directs[$market1.'_'.$create_time1.'_'.$order['direct']])){
                            $directs[$market1.'_'.$create_time1.'_'.$order['direct']] = 0;
                            $directs_ids[$market1.'_'.$create_time1.'_'.$order['direct']] = [];
                        }
                        $directs[$market1.'_'.$create_time1.'_'.$order['direct']]++;
                        $directs_ids[$market1.'_'.$create_time1.'_'.$order['direct']][] = $order['id'];
                    }
                }
            }
            $this->logger('[控制K线]','kline')->info('$directs:'.json_encode($directs));
            $this->logger('[控制K线]','kline')->info('$directs_ids:'.json_encode($directs_ids));
            // 6、判断同一方向的有没有大于10个订单的
            foreach ($directs as $key4=>$item4) {
                if ($item4 >= $max_count) {
                    $market_direct_arr[] = $key4;
                }
            }
            $this->logger('[控制K线]','kline')->info('同一方向:'.json_encode($market_direct_arr));
            if ($market_direct_arr) {
                $sum = [];
                $sum_ids = [];
                // 7、计算同一币种、同一时间、同一方向订单下单金额总额
                foreach ($market_direct_arr as $item5) {
                    [$market2,$create_time2,$direct2]=explode('_', $item5);
                    foreach ($market_order_arr[$market2] as $order) {
                        if(in_array($order['id'], $directs_ids[$item5])){
                            if(!isset($sum[$market2.'_'.$create_time2.'_'.$direct2.'_num'])){
                                $sum[$market2.'_'.$create_time2.'_'.$direct2.'_num'] = 0;
                                $sum_ids[$market2.'_'.$create_time2.'_'.$direct2.'_num'] = [];
                            }
                            $sum[$market2.'_'.$create_time2.'_'.$direct2.'_num']+=$order['num'];
                            $sum_ids[$market2.'_'.$create_time2.'_'.$direct2.'_num'][] = $order['id'];
                        }
                    }
                }
                $this->logger('[控制K线]','kline')->info('$sum:'.json_encode($sum));
                $this->logger('[控制K线]','kline')->info('$sum_ids:'.json_encode($sum_ids));

                foreach ($sum_ids as $key6=>$item6) {
                    if ($sum[$key6] >= $max_num) {
                        $market_num_arr[] = $key6;
                    }
                }
                $this->logger('[控制K线]','kline')->info('下单总额:'.json_encode($market_num_arr));
                $should_settle_times = [];
                $should_settle_times_id_arr = [];
                $target_market = '';
                $target_price = '';
                $target_direct = '';
                $prices = [];
                // 9、拿到这些订单的应结算时间
                foreach ($market_num_arr as $item7) {
                    [$market2,$createTime2,$direct2]=explode('_', $item7);
                    $target_market = $market2;

                    $order_id_arr = $sum_ids[$item7];
                    foreach ($order_id_arr as $order_id) {
                        $prices[] = $market_order_info_arr[$order_id]['price'];
                    }
                    // 买涨，则控跌，拿最低价
                    if ($direct2 == '1') {
                        $target_price = min($prices);
                    } else {
                        $target_price = max($prices);
                    }
                    $target_direct = $direct2;
                    if (count($market_order_arr[$market2]) >= $max_count) {
                        foreach ($market_order_arr[$market2] as $order) {
                            if(in_array($order['id'], $sum_ids[$item7])){
                                $should_settle_times[] = $order['should_settle_time'];
                                $should_settle_times_id_arr[$order['should_settle_time']] = $order['id'];
                            }
                        }
                        // 只拿第一组
                        break;
                    }
                }
                $this->logger('[控制K线]','kline')->info('$should_settle_times:'.json_encode($should_settle_times));
                $this->logger('[控制K线]','kline')->info('$should_settle_times_id_arr:'.json_encode($should_settle_times_id_arr));
                $this->logger('[控制K线]','kline')->info('$target_market:'.$target_market);
                $this->logger('[控制K线]','kline')->info('$target_price:'.$target_price);
                $this->logger('[控制K线]','kline')->info('$target_direct:'.$target_direct);
                /**
                 * 上面已经拿到了达到10单的相同币种、买入价、方向且下单金额大于指定数值的订单的id了，这里要考虑下单延迟的问题，也就是这10个
                 * 订单的应结算时间会有1s、2s等差距，得涵盖处理
                 */
                $target_order_should_settle_times = [];
                foreach ($should_settle_times_id_arr as $key8=>$item8) {
                    $target_order_should_settle_times[] = $key8;
                }

                $this->logger('[控制K线]','kline')->info('$target_order_should_settle_times:'.json_encode($target_order_should_settle_times));

                if (count($should_settle_times_id_arr) > 0 && count($target_order_should_settle_times) > 0) {
                    $target_start_time = intval(min($target_order_should_settle_times) / 1000);
                    $target_end_time = intval(max($target_order_should_settle_times) / 1000);

                    $price = $target_price; // 订单买入价
                    $market = $target_market;
                    $direct = $target_direct;

                    // 查是否和正常控K撞线
                    $secondKline = $this->app(SysSecondKline::class)->getQuery()->orderBy('trade_time','desc')->first('trade_time');
                    $pre_time =  $secondKline->trade_time;
                    $this->logger('[控制K线]','kline')->info('判断是否和正常控K撞线:'. $secondKline->trade_time );
                    $pre_time = strtotime($pre_time); // 结束时间
                    $pre_time_before = bcsub(strval($pre_time),'23',0); // 正常控K开始时间，单位s  42
                    $current_pre_time_before = bcsub(strval($target_start_time),'2',0); // 应结算将要控K的开始时间
                    // 判断是否和正常控K撞线
                    if ($current_pre_time_before < $pre_time_before || $current_pre_time_before > $pre_time) { // 没撞线
                        $this->logger('[控制K线]','kline')->info('没撞线');
                        // 拿到目标用户的应结算时间，判断，是否在56s~05s之间，如果不在，直接爆。如果在，再去看58s~03s应结算单量人数，如果够100，不爆，反之，爆他
                        $s = intval(date('s', intval($target_start_time)));
                        if ($s >= 56 || $s <= 5) {  // 在56s~05s之间
                            $this->logger('[控制K线]','kline')->info('在56s~05s之间');
                            // 处理该订单应结算时间所在分钟的58s~03s
                            if ($s >= 0 && $s <= 5) {
                                $this->logger('[控制K线]','kline')->info('处理该订单应结算时间所在分钟的58s~03s  0-5');
                                $end = strtotime(date('Y-m-d H:i:03', intval($target_start_time)));
                                $start = bcsub(strval($end),'5',0);
                            } else {
                                $this->logger('[控制K线]','kline')->info('0-5???');
                                $start = strtotime(date('Y-m-d H:i:58', intval($target_start_time)));
                                $end = bcadd(strval($start),'5',0);
                            }
                            $start = intval($start) * 1000;
                            $end = intval($end) * 1000;
                            // 查看58s~03s应结算单量人数  , 排除带单老师号
                            $userIds = explode('@',$this->app(SysConfigService::class)->value('blasting_team_ids'));
                            $order_count = $this->app(UserSecondService::class)->getQuery()->where(['settle_status'=>0])->where('should_settle_time','>=', $start)->where('should_settle_time', '<=', $end)->whereIn('user_id',$userIds)->count();
                            if ($order_count == 0) {
                                $this->app(UserSecondService::class)->getQuery()->whereIn('should_settle_time',$target_order_should_settle_times)->update(['is_kong' => 1]);
                                $this->blasting_team($target_start_time, $target_end_time, $price,  $market, $direct);
                            }
                        }else{
                            $this->app(UserSecondService::class)->getQuery()->whereIn('should_settle_time',$target_order_should_settle_times)->update(['is_kong' => 1]);
                            $this->logger('[控制K线]','kline')->info('在56s~05s之间???');
                            $this->blasting_team($target_start_time, $target_end_time, $price, $market, $direct);
                        }
                    }
                }
            }
        }
    }



}