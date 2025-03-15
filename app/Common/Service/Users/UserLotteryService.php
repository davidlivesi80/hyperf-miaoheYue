<?php


namespace App\Common\Service\Users;



use App\Common\Model\System\SysLotteryList;
use App\Common\Service\Lottery\LotteryService;
use App\Common\Service\System\SysLotteryListService;
use App\Job\SecondJob;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserLotteryLogic;
use App\Common\Service\System\SysLotteryService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\Subscribe\ChannelRecordData;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class UserLotteryService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserLotteryLogic
     */
    public function __construct(UserLotteryLogic $logic)
    {
        $this->logic = $logic;

    }

    /**
     * 查询构造
     */
    public function search(array $where, $with=[],$page=1,$perPage = 10){

        $list = $this->logic->search($where)->with($with)->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 订单统计
     */
    public function counts(array $where){

        $list['total_num'] = $this->logic->search($where)->sum('num');
        $list['total_count'] = $this->logic->search($where)->count();
        if($where['fee_rate']){//重仓
            $list['fee_num'] = $this->logic->search($where)->sum('num');
            $list['fee_count'] = $this->logic->search($where)->count();
        }else{
            $list['fee_num'] = 0;
            $list['fee_count'] = 0;
        }
        //全网重仓比例
        $list['fee_rate'] = 0;
        //下午场第一至第五单理论值
        $configService= $this->app(SysConfigService::class);
        $now_m_s = date('H:i');
        $scene = $this->checkScene($now_m_s,$configService);
        $lilun_num_1=0; $lilun_num_2= 0; $lilun_num_3= 0; $lilun_num_4= 0; $lilun_num_5 = 0;
        if($scene == 1){
            $lilun_num_one = explode('@', $configService->value('lilun_num_one'));
            $lilun_num_1 = isset($lilun_num_one[0]) ? $list['total_count'] * $lilun_num_one[0] : 0;
            $lilun_num_2 = isset($lilun_num_one[1]) ? $list['total_count'] * $lilun_num_one[1] : 0;
            $lilun_num_3 = isset($lilun_num_one[2]) ? $list['total_count'] * $lilun_num_one[2] : 0;
            $lilun_num_4 = isset($lilun_num_one[3]) ? $list['total_count'] * $lilun_num_one[3] : 0;
            $lilun_num_5 = isset($lilun_num_one[4]) ? $list['total_count'] * $lilun_num_one[4] : 0;
        }elseif($scene == 2){
            $lilun_num_two = explode('@', $configService->value('lilun_num_two'));
            $lilun_num_1 = isset($lilun_num_two[0]) ? $list['total_count'] * $lilun_num_two[0] : 0;
            $lilun_num_2 = isset($lilun_num_two[1]) ? $list['total_count'] * $lilun_num_two[1] : 0;
            $lilun_num_3 = isset($lilun_num_two[2]) ? $list['total_count'] * $lilun_num_two[2] : 0;
            $lilun_num_4 = isset($lilun_num_two[3]) ? $list['total_count'] * $lilun_num_two[3] : 0;
            $lilun_num_5 = isset($lilun_num_two[4]) ? $list['total_count'] * $lilun_num_two[4] : 0;
        }
        $list['lilun_num_1'] = $lilun_num_1;
        $list['lilun_num_2'] = $lilun_num_2;
        $list['lilun_num_3'] = $lilun_num_3;
        $list['lilun_num_4'] = $lilun_num_4;
        $list['lilun_num_5'] = $lilun_num_5;

        return $list;
    }

    /**
     * 并非统计
     */
    public function minute(array $where){

        $list = $this->logic->search($where)->selectRaw('COUNT(*) as count,created_at')->groupBy('created_at')->get();

        return $list;
    }

    /**
     * 查询构造
     */
    public function searchApi(array $where,$limit=10){

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','lottery:id,title'])->limit($limit)->get();
        foreach ($list as $key=>$value){
            $list[$key]['price'] = bcadd('0',$value['price'],2);
            $list[$key]['settle_price'] = bcadd('0',$value['settle_price'],2);

        }
        return $list;

    }

    /**
     * 查询构造
     */
    public function searchWin(array $where,$limit=10){

        $markets = $this->app(SysLotteryService::class)->searchApi();
        $market = "";$list=[];
        foreach ($markets as $item){
            if($where['second_id'] == $item['id']){
                $market = $item['market'];
                break;
            }
        }

        if(!empty($market)){
            $now_m_s =  strtotime(date('Y-m-d H:i:00'));//当前分钟数据
            $lst_m_s = $now_m_s-60;
            $cache_key ="{$market}:fount:1min:{$now_m_s}";
            $last_key ="{$market}:fount:1min:{$lst_m_s}";
            $cache_data =$this->getCache()->get($cache_key);
            $last_data =$this->getCache()->get($last_key);
            if(!empty($cache_data)){
                foreach (json_decode($cache_data,true) as $value){
                    $list[] = $value;
                }
            }
            if(!empty($last_data)){
                foreach (json_decode($last_data,true) as $value){
                    $list[] = $value;
                }
            }
        }
        return $list;
    }

    /**
     * 添加
     * 规则：$lottery_wei 1个位  2十位  3 十位 + 个位
     *      $lottery_bit 1大  2小  3 单  4 双   0-9数字组合
     */
    public function create($userId,$lotteryId,$lottery_wei,$lottery_bit,$lottery_type,$lottery_num,$lottery_bei=1){
        $start_time = $this->get_millisecond();
        //查看交易对状态
        $lottery = $this->app(SysLotteryService::class)->searchCache(['id'=>$lotteryId]);
        if(!$lottery){
            throw new AppException('lottery_status_error',400);//交易不存在
        }
        if( $lottery_type == 3 && $lottery_wei == 2 ){
            throw new AppException('lottery_wei_later',400);//猜数值不支持十位
        }
        if( $lottery_type == 3 && $lottery_wei == 1 ){
            $lottery_num_arr = $this->checkNumber($lottery_wei,$lottery_bit);
            if(count($lottery_num_arr) > $lottery_num){
                throw new AppException('lottery_num_error',400);//投资金额错误
            }
        }
        if( $lottery_type == 3 && $lottery_wei == 3 ){
            $lottery_num_arr = $this->checkNumber($lottery_wei,$lottery_bit);
            if(0>=count($lottery_num_arr)){
                throw new AppException('lottery_check_error',400);//数字组合错误
            }
            if((count($lottery_num_arr) * $lottery_bei) > $lottery_num){
                throw new AppException('lottery_num_error',400);//投资金额错误
            }
        }
        //查询赔率
        $lottery_rate = $this->computeRate($lottery['attres'] ,$lottery_wei,$lottery_bit,$lottery_type);
        if(0>=$lottery_rate){
            throw new AppException('lottery_rate_error',400);//不支持该赔率玩法
        }
        /** @var  $ConfigService ConfigService*/
        $configService= $this->app(SysConfigService::class);
        // 判断该用户是否恶意用户
        $is_malice = $this->app(UserService::class)->is_malice($userId);
        list($create_time,$should_settle_time,$is_delay) = $this->checkMalice($is_malice,$start_time,$configService);
        // 查看是否充足
        $currency = strtolower('usdt');
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($lottery_num) > $balance[$currency]){
            throw new AppException('insufficient_balance',400);//余额不足
        }
        //位置计算
        if($lottery_wei == 1){
            $bits = $lottery_bit; $ten_bits = "";

        }elseif ($lottery_wei == 2){
            $bits = ""; $ten_bits = $lottery_bit;
        }elseif ($lottery_wei == 3){
            $lottery_bit_arr = explode('|',$lottery_bit);
            $bits = $lottery_bit_arr[0]; $ten_bits = $lottery_bit_arr[1];
        }

        Db::beginTransaction();
        try {
            //组装数据
            $orderSn = $this->makeOrdersn('JC');
            $data = [
                'user_id' => $userId,
                'order_sn' => $orderSn,
                'lottery_id'=> $lottery['id'],
                'lottery_type'=> $lottery_type,
                'symbol'=> $currency,
                'num'=> $lottery_num,
                'bei'=> $lottery_bei,
                'time'=> $start_time,
                'wei_bits'=> $lottery_wei,
                "bits"=> $bits ,//
                "ten_bits"=>  $ten_bits ,//
                'rate'=> $lottery_rate,// 赔付率
                'should_settle_time'=> $this->checkLotteryTime(time()),//开奖K线时间
                'pay_type'=>1,
                'created_at'=> $create_time,
                'updated_at'=> date('YmdHis',bcdiv(strval($start_time),strval(1000),0)),
                'date'=> date('Y-m-d',bcdiv(strval($start_time),strval(1000),0)),//这个函数需要秒 毫秒得÷1000
            ];
            //创建
            $order = $this->logic->create($data);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //扣除余额
            $subNum = bcmul((string)$data['num'],'-1',6);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($data['user_id'],$currency,$balance[$currency],$subNum,12,'竞猜下单',$orderSn);
            if($res !== true){
                throw new \Exception('Asset failed');//资产失败
            }

            $data['should_settle_time'] = strval($data['should_settle_time'] + 0);
            Db::commit();
            return $data;
        } catch(\Throwable $e){
            //写入错误日志
            Db::rollBack();
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[跟单下单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }


    /*检查交易时间*/
    public function checkTime($second,$time){

    }
    /*一分钟内同一币种禁止超过3单*/
    public function checkOrderNums($userId,$lottery,$configService){

    }
    /*校验规避恶意用户 ，如果是则将其下单时间放到s*/
    public function checkMalice($is_malice, $start_time ,$configService){
        $current_time = bcdiv(strval($start_time),strval(1000),0);
        if ($is_malice) { // 黑名单
            $create_time = date('Y-m-d H:i:00',bcdiv(strval($start_time),strval(1000),0));//这个函数需要秒 毫秒得÷1000
            $should_settle_time = bcadd(strval(300*1000),strval($start_time),6);//应结算时间
            $is_delay = 0;
        } else {
            $create_time = date('Y-m-d H:i:00',bcdiv(strval($start_time),strval(1000),0));//这个函数需要秒 毫秒得÷1000
            $should_settle_time = bcadd(strval(300*1000),strval($start_time),6);//应结算时间
            $is_delay = 0;
        }
        return [$create_time,$should_settle_time,$is_delay];
    }

    /*根据K线预设开奖数据*/
    public function checkLottery($lottery_id,$kline_key){
        $close_s = $this->checkKlineTime();
        $kline = $this->checklineClose($kline_key,$close_s);
        $lottery_sn =  date('YmdHi',($close_s));
        $LotterySn =   $this->app(SysLotteryListService::class)->getQuery()->where('lottery_id',$lottery_id)->where('sn',$lottery_sn)->first();
        if($LotterySn){
            $data['kline'] = json_encode($kline);
            $data['price'] = isset($kline['close']) ? $kline['close'] :0;
            $this->app(SysLotteryListService::class)->getQuery()->where('id',$LotterySn->id)->update($data);
        }else{
            $data['lottery_id'] = $lottery_id;
            $data['sn'] = $lottery_sn;
            $data['kline'] = json_encode($kline);
            $data['price'] = isset($kline['close']) ? $kline['close'] :0;
            $this->app(SysLotteryListService::class)->create($data);
        }
    }

    /*开奖数据生产-预设*/
    public function checkKlineTime(){
        $close_date=  date('Y-m-d H:00:00');
        $close_minute = date('i');
        $close_start = strtotime(date($close_date));
        if(in_array($close_minute,['00','01','02','03','04'])){
            $close_s = $close_start;
        }elseif (in_array($close_minute,['05','06','07','08','09'])){
            $close_s = $close_start + 300;
        }elseif (in_array($close_minute,['10','11','12','13','14'])){
            $close_s = $close_start + 600;
        }elseif (in_array($close_minute,['15','16','17','18','19'])){
            $close_s = $close_start + 900;
        }elseif (in_array($close_minute,['20','21','22','23','24'])){
            $close_s = $close_start + 1200;
        }elseif (in_array($close_minute,['25','26','27','28','29'])){
            $close_s = $close_start + 1500;
        }elseif (in_array($close_minute,['30','31','32','33','34'])){
            $close_s = $close_start + 1800;
        }elseif (in_array($close_minute,['35','36','37','38','39'])){
            $close_s = $close_start + 2100;
        }elseif (in_array($close_minute,['40','41','42','43','44'])){
            $close_s = $close_start + 2400;
        }elseif (in_array($close_minute,['45','46','47','48','49'])){
            $close_s = $close_start + 2700;
        }elseif (in_array($close_minute,['50','51','52','53','54'])){
            $close_s = $close_start + 3000;
        }elseif (in_array($close_minute,['55','56','57','58','59'])){
            $close_s = $close_start + 3300;
        }
        return $close_s;
    }

    /*计算结算时间、小于该时间都结算*/
    public function checkSettleTime(){
        $close_s = $this->checkKlineTime();//当前正在运行的期号时间
        $sellet_sn =  date('YmdHi',($close_s - 300));//本次结算的期号
        return $sellet_sn;
    }
    /*猜数字排列组合*/
    public function checkNumber($lottery_wei,$lottery_bit){
       if($lottery_wei == 1){
           $lottery_num_arr = explode('@',$lottery_bit);
       }elseif ($lottery_wei == 3){
           $lottery_bit_arr = explode('|',$lottery_bit);
           $ten_bits = explode('@',$lottery_bit_arr[1]);
           $bits = explode('@',$lottery_bit_arr[0]);
           $lottery_num_arr = [];
           foreach ($ten_bits as $num1) {
               foreach ($bits as $num2) {
                   $lottery_num_arr[] = $num1."".$num2;
               }
           }
       }
       return $lottery_num_arr;
    }

    /*开奖时间生产-结算*/
    public function checkLotteryTime($close_m){
        $close_date=  date('Y-m-d H:00:00',$close_m);
        $close_minute = date('i',$close_m);
        $close_start = strtotime(date($close_date));
        if(in_array($close_minute,['00','01','02','03','04'])){
            $close_s = $close_start + 300;
        }elseif (in_array($close_minute,['05','06','07','08','09'])){
            $close_s = $close_start + 600;
        }elseif (in_array($close_minute,['10','11','12','13','14'])){
            $close_s = $close_start + 900;
        }elseif (in_array($close_minute,['15','16','17','18','19'])){
            $close_s = $close_start + 1200;
        }elseif (in_array($close_minute,['20','21','22','23','24'])){
            $close_s = $close_start + 1500;
        }elseif (in_array($close_minute,['25','26','27','28','29'])){
            $close_s = $close_start + 1800;
        }elseif (in_array($close_minute,['30','31','32','33','34'])){
            $close_s = $close_start + 2100;
        }elseif (in_array($close_minute,['35','36','37','38','39'])){
            $close_s = $close_start + 2400;
        }elseif (in_array($close_minute,['40','41','42','43','44'])){
            $close_s = $close_start + 2700;
        }elseif (in_array($close_minute,['45','46','47','48','49'])){
            $close_s = $close_start + 3000;
        }elseif (in_array($close_minute,['50','51','52','53','54'])){
            $close_s = $close_start + 3300;
        }elseif (in_array($close_minute,['55','56','57','58','59'])){
            $close_s = $close_start + 3600;
        }
        return $close_s;
    }

    /*获取上一个5分钟K线*/
    public function checklineClose($kline_key,$close_s){
        $key = $kline_key.':'. $close_s;
        $kline = $this->app(ChannelRecordData::class)->all($key);
        return  $kline;
    }
    /*计算状态、赔率*/
    public function computeSettle($order_rate,$lottery_wei,$bits,$ten_bits,$lottery_type,$close_price){
        if ($lottery_type == 1){//大小
            if($lottery_wei == 1){ // 个位
                [$gewei,$shiwei] = $this->app(LotteryService::class)->winning('dx', $lottery_wei,$bits,$close_price);
            }elseif ($lottery_wei == 2){ // 十位
                [$gewei,$shiwei] = $this->app(LotteryService::class)->winning('dx', $lottery_wei,$ten_bits,$close_price);
            }
            if($gewei > 0 ){// $lottery_bits 1大 2小
                $profit_status = 1;  $profit_rate = bcadd('0',(string)$order_rate,4);
            }elseif ($shiwei > 0 ){
                $profit_status = 1;  $profit_rate = bcadd('0',(string)$order_rate,4);
            }else{
                $profit_status = 2;  $profit_rate = 100;
            }
        }elseif($lottery_type == 2){//单双
            if($lottery_wei == 1){ // 个位
                [$gewei,$shiwei] = $this->app(LotteryService::class)->winning('ds', $lottery_wei,$bits,$close_price);
            }elseif ($lottery_wei == 2){ // 十位
                [$gewei,$shiwei] = $this->app(LotteryService::class)->winning('ds', $lottery_wei,$ten_bits,$close_price);
            }
            if($gewei > 0){// $lottery_bits = 3单4双
                $profit_status = 1;  $profit_rate = bcadd('0',(string)$order_rate,4);
            }elseif ($shiwei > 0){
                $profit_status = 1;  $profit_rate = bcadd('0',(string)$order_rate,4);
            }else{
                $profit_status = 2;  $profit_rate = 100;
            }
        }elseif($lottery_type == 3){//数值
            $geweiTotal = 0;$shiweiTotal=0;
            $lottery_bits = [];
            if($lottery_wei == 1){ // 个位
                //组合数字
                $lottery_num_arr = $this->checkNumber($lottery_wei,$bits);
                foreach ($lottery_num_arr as $item) {
                    [$gewei,$shiwei] = $this->app(LotteryService::class)->winning('shuzhi',$lottery_wei, $item, $close_price);
                    $geweiTotal = $geweiTotal  + $gewei;
                }

            }elseif ($lottery_wei == 3){ // 个位十位
                //组合数字
                $lottery_bits = $bits . "|" . $ten_bits;
                $lottery_num_arr = $this->checkNumber($lottery_wei,$lottery_bits);
                foreach ($lottery_num_arr as $item){
                    [$gewei,$shiwei] = $this->app(LotteryService::class)->winning('shuzhi',$lottery_wei, $item, $close_price);
                    $shiweiTotal = $shiweiTotal  + $shiwei;
                }
            }
            if($geweiTotal > 0){
                $profit_status = 1;  $profit_rate = bcmul((string)$geweiTotal ,(string)$order_rate,4);
            }elseif ($shiweiTotal > 0){
                $profit_status = 1;  $profit_rate = bcmul((string)$shiweiTotal,(string)$order_rate,4);
            }else{
                $profit_status = 2;  $profit_rate = 100;
            }
        }
        return [$profit_status,$profit_rate];
    }

    public function computeRate($attres,$lottery_wei,$lottery_bit,$lottery_type){
        $attr_ids = array_column($attres,'attr_id');
        if ($lottery_type == 1){//大小
            if($lottery_wei == 1){ // 个位
                if($lottery_bit == 1){//大
                    $lottery_rate =$attres[array_search(1,$attr_ids)]['value'];
                }
                if($lottery_bit == 2){//小
                    $lottery_rate =$attres[array_search(2,$attr_ids)]['value'];
                }
            }elseif ($lottery_wei == 2){ // 十位
                if($lottery_bit == 1){//大
                    $lottery_rate =$attres[array_search(3,$attr_ids)]['value'];
                }
                if($lottery_bit == 2){//小
                    $lottery_rate =$attres[array_search(4,$attr_ids)]['value'];
                }
            }

        }elseif($lottery_type == 2){//单双
            if($lottery_wei == 1){ // 个位

                if($lottery_bit == 3){//单
                    $lottery_rate =$attres[array_search(5,$attr_ids)]['value'];
                }
                if($lottery_bit == 4){//双
                    $lottery_rate =$attres[array_search(6,$attr_ids)]['value'];
                }
            }elseif ($lottery_wei == 2){ // 十位
                if($lottery_bit == 3){//单
                    $lottery_rate =$attres[array_search(7,$attr_ids)]['value'];
                }
                if($lottery_bit == 4){//双
                    $lottery_rate =$attres[array_search(8,$attr_ids)]['value'];
                }
            }
        }elseif($lottery_type == 3){//单双
            if($lottery_wei == 1){ // 个位
                $lottery_rate =$attres[array_search(9,$attr_ids)]['value'];
            }elseif ($lottery_wei == 2){ // 十位
                $lottery_rate =0;
            }elseif ($lottery_wei == 3){ // 个位 + 十位
                $lottery_rate =$attres[array_search(10,$attr_ids)]['value'];
            }
        }else{
            $lottery_rate = 0;
        }
        return  $lottery_rate;
    }

    /*取消订单*/
    public function cancel($order){
        
        if(!$order || $order->status == 0){
            throw new AppException('订单已处理',400);
        }

        $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);

        Db::beginTransaction();
        try {
            $rel = $this->logic->getQuery()->where(['id'=>$order->id])->update(['status'=>0]);
            if(!$rel){
                throw new \Exception('Order failed');//资产失败
            }
            $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->symbol,$balance[$order->symbol],$order->num,14,'竞猜撤单',$order->id);
            if($res !== true){
                throw new \Exception('Asset failed');//资产失败
            }
            Db::commit();
            //更新业绩
           return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[竞猜撤单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*静态 盈利结算*/
    public function income($order,$closePrice = 0){
        if ($order->settle_status > 0 ) {
            return false;
        }

        $configService= $this->app(SysConfigService::class);
        //获取K线
        $should_settle_time = strval($order->should_settle_time);// 毫秒->秒
        $kline_key = implode(':',["btcusdt",'5min']);
        $kline =  $this->checklineClose($kline_key,strval($order->should_settle_time));
        $this->logger('[获取竞猜5分钟K线数据]','error')->info(json_encode(['分钟'=>date("Y-m-d H:i:00", $should_settle_time),'kline'=>$kline],JSON_UNESCAPED_UNICODE));
        if($closePrice > 0){
            $close_price = $closePrice;
        }else{
            $close_price = isset($kline['close']) ? $kline['close'] : 0;
        }
        if(!$close_price){
            return false;
        }

        //判断盈亏,获取对应结算比例
        list($profit_status,$profit_rate) = $this->computeSettle($order->rate,$order->wei_bits,$order->bits,$order->ten_bits,$order->lottery_type,$close_price);
            $this->logger('[获取竞猜5分钟K线数据]','error')->info(json_encode(['close_price'=>$close_price,'profit_status'=>$profit_status,'profit_rate'=>$profit_rate],JSON_UNESCAPED_UNICODE));
        $update['settle_price'] = $close_price;//结算价
        $update['profit_status']= $profit_status;//盈亏状态  1盈 2亏
        //本金 * 盈亏比例% = 盈亏金额
        if($order->lottery_type == 3 && $profit_status == 1){
            $profit = bcdiv(bcmul(strval($profit_rate),'1',6),strval(100),6);
            //盈
            $capital = bcadd("0",$profit,6);//本金 + 亏损金额 = 剩余金额
        }else{
            $profit = bcdiv(bcmul(strval($profit_rate),strval($order->num),6),strval(100),6);
            if($update['profit_status']==1){
                //盈
                $capital = bcadd(strval($order->num),$profit,6);//本金 + 亏损金额 = 剩余金额
            }elseif($update['profit_status']==2){
                //亏
                $capital = bcsub(strval($order->num),$profit,6);//本金 - 亏损金额 = 剩余金额
            }
        }
        $profit = bcmul($profit,strval($order->bei),6);//计算倍率
        $update['profit']=$profit;//盈亏金额
        $update['settle_status']=1;//已结算
        $update['settle_time']=time();//结算时间
        $update['capital'] = $capital;
        Db::beginTransaction();
        try {
            $res = $this->logic->getQuery()->where(['id'=>$order->id,'settle_status'=>0])->update($update);
            if (!$res) {
                throw new \Exception('结算失败');
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            if($update['capital'] > 0){
                $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $update['capital'], 13, '竞猜结算', $order->id);
                if ($rel !== true) {
                    throw new \Exception('更新资产失败');
                }
            }
            if($update['capital'] >= 0){
                $record = [
                    'user_id'=>$order->user_id,
                    'order_id'=>$order->id,
                    'lottery_id'=>$order->lottery_id,
                    'total'=>$order->num,
                    'symbol'=>'usdt',
                    'rate'=>$profit_rate,
                    'reward'=> $profit,//今日收益代币-盈亏金额
                    'reward_type'=> $profit_status,//1盈利  2 亏损 3平
                    'reward_time'=>time(),// 毫秒->秒
                ];
                $this->app(UserLotteryIncomeService::class)->getQuery()->insert($record);
            }
            Db::commit();
            //更新奖金
            $income = $this->app(UserLotteryIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',1)->sum('reward');
            $deficit = $this->app(UserLotteryIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',2)->sum('reward');
            if($income >= 0){
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['income_lottery'=>$income,'deficit_lottery'=>$deficit]);
            }
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[盈利结算]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*静态 盈利统计*/
    public function incomeSettle($order){

        Db::beginTransaction();
        try {
            Db::commit();
            //更新奖金
            $income = $this->app(UserLotteryIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',1)->sum('reward');
            $deficit = $this->app(UserLotteryIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',2)->sum('reward');
            if($income >= 0){
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['income_lottery'=>$income,'deficit_lottery'=>$deficit]);
            }
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[盈利结算]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }



}