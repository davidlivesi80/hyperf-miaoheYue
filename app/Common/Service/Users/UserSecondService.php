<?php


namespace App\Common\Service\Users;


use App\Common\Service\Subscribe\ChannelRecordData;
use App\Common\Service\System\SysSecondService;
use App\Common\Service\System\SysConfigService;
use App\Common\Service\System\SysSportRuningService;
use App\Job\SecondJob;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSecondLogic;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class UserSecondService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserSecondLogic
     */
    public function __construct(UserSecondLogic $logic)
    {
        $this->logic = $logic;

    }

    /**
     * 查询构造
     */
    public function search(array $where, $with=[],$page=1,$perPage = 10,$sort="id",$order="desc"){

        $list = $this->logic->search($where)->with($with)->orderBy($sort, $order)->paginate($perPage,['*'],'page',$page);

        $list->each(function ($item) use ($where){

            $item['fee_rate'] = bcmul($item['fee_rate'],'100',4);
            return $item;
        });
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
        //普通场
        $where['scene']=1;$lilun_one_count = $this->logic->search($where)->count();
        $lilun_num_one = explode('@', $configService->value('lilun_num_one'));
        $lilun_num_1 = isset($lilun_num_one[0]) ? $lilun_one_count * $lilun_num_one[0] : 0;
        $lilun_num_2 = isset($lilun_num_one[1]) ? $lilun_one_count * $lilun_num_one[1] : 0;
        $lilun_num_3 = isset($lilun_num_one[2]) ? $lilun_one_count * $lilun_num_one[2] : 0;
        $lilun_num_4 = isset($lilun_num_one[3]) ? $lilun_one_count * $lilun_num_one[3] : 0;
        $lilun_num_5 = isset($lilun_num_one[4]) ?$lilun_one_count * $lilun_num_one[4] : 0;
        //赔付场
        $where['scene']=2;$lilun_two_count = $this->logic->search($where)->count();
        $lilun_num_two = explode('@', $configService->value('lilun_num_two'));
        $lilun_num_6 = isset($lilun_num_two[0]) ? $lilun_two_count * $lilun_num_two[0] : 0;
        $lilun_num_7 = isset($lilun_num_two[1]) ? $lilun_two_count * $lilun_num_two[1] : 0;
        $lilun_num_8 = isset($lilun_num_two[2]) ? $lilun_two_count * $lilun_num_two[2] : 0;
        $lilun_num_9 = isset($lilun_num_two[3]) ? $lilun_two_count * $lilun_num_two[3] : 0;
        $lilun_num_10 = isset($lilun_num_two[4]) ? $lilun_two_count * $lilun_num_two[4] : 0;

        $list['lilun_num_1']  = $lilun_num_1;
        $list['lilun_num_2']  = $lilun_num_2;
        $list['lilun_num_3']  = $lilun_num_3;
        $list['lilun_num_4']  = $lilun_num_4;
        $list['lilun_num_5']  = $lilun_num_5;
        $list['lilun_num_6']  = $lilun_num_6;
        $list['lilun_num_7']  = $lilun_num_7;
        $list['lilun_num_8']  = $lilun_num_8;
        $list['lilun_num_9']  = $lilun_num_9;
        $list['lilun_num_10'] = $lilun_num_10;

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

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','second:id,market,status'])->orderBy("id", "desc")->limit($limit)->get();

        foreach ($list as $key=>$value){
            if(strtolower($value['market']) == "btcusdt"){
                $list[$key]['price'] = bcadd('0',$value['price'],2);
                $list[$key]['settle_price'] = bcadd('0',$value['settle_price'],2);
            }
        }

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchWin(array $where,$limit=10){

        $markets = $this->app(SysSecondService::class)->searchApi();
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
     */
    public function create($userId,$market,$direct,$period,$num){
        $start_time = $this->get_millisecond();
        //查看交易对状态
        $second = $this->app(SysSecondService::class)->searchApi($market);
        if(empty($second) || $second['status'] ==0){
            throw new AppException('second_card_error',400);//交易不存在
        }
        if(!in_array($period,$second['trade_period_arr'])){
            throw new AppException('second_period_error',400);//周期不存在
        }
        //标记临时体验结束，不可下单
//        if($this->checkRegisLock($userId,2)){
//            throw new AppException('second_regis_lock',400);//体验结束，不可下单
//        }
        /*禁止交易时间*/
        $now_m_s = date('H:i');
        if(!$this->checkTime($second,$now_m_s)){
            throw new AppException('second_time_error',400);//当前时间禁止交易
        }
        /** @var  $ConfigService ConfigService*/
        $configService= $this->app(SysConfigService::class);
        // 一分钟内同一币种禁止超过3单
        if(!$this->checkOrderNums($userId,$second,$configService)){
            throw new AppException('second_down_error',400);//禁止超过3单,请勿频繁操作
        }
        // 检验获取场次校验
        $scene = $this->checkScene($now_m_s,$configService);
        if($scene==0){
            throw new AppException('second_time_off',400);//当前时间场次未开放购买
        }
        if(!$this->checkSceneNum($num,$scene,$configService)){
            throw new AppException('second_num_error',400); //'下单数量错误：' . $min_num . '~' . $max_num
        }
        // 检验K线数据  PS有没有当前时间点的K线数据  没有就获取上一分钟的数据
        $now_m = strtotime(date('Y-m-d H:i:00'));//当前分钟数据
        $kline_key = implode(':',[$second['market'],'1min']);
        $kline = $this->checkline($kline_key,$now_m);
        if(!$kline){
            throw new AppException('second_kline_error',400);//场次不存在，其实就是没K线数据
        }
        // 判断该用户是否恶意用户，如果是则将其下单时间放到02s  03s
        $is_malice = $this->app(UserService::class)->is_malice($userId);
        list($create_time,$should_settle_time,$is_delay) = $this->checkMalice($is_malice,$num,$period,$start_time,$configService);
        $teacher_id =  $configService->value('teacher_scene_'. $scene);//获取该场次带单老师ID
        if($userId != $teacher_id && !$is_malice){
            // 判断是不是老师已经下单,根据老师数据用户跟单,前提用户买入方向和场次以及涨跌必须一致,才可以跟老师数据
            list($is_teacher,$create_time,$should_settle_time,$kline['close']) = $this->checkTeacher($second['market'],$create_time,$should_settle_time,$kline['close'],$start_time,$scene,$period,$direct,$configService);
        }
        // 查看是否充足
        $parentIds = $this->app(UserRelationService::class)->getParent($userId);
        if(in_array(1,$parentIds) || $userId == 1){
            $order_type = 1;
        }else{
            $order_type = 0;
        }

        $currency  = 'usdt';
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($num) > $balance[$currency]){
            throw new AppException('insufficient_balance',400);//余额不足
        }
        try {
            //组装数据
            $orderSn = $this->makeOrdersn('T');
            $data = [
                'user_id' => $userId,
                'order_sn' => $orderSn,
                'second_id'=> $second['id'],
                'market'=> $second['market'],
                'symbol'=> $currency,
                'num'=>$num,
                'time'=>$start_time,
                'price'=>$kline['close'],
                "fee"=>$balance[$currency],//重仓
                "fee_rate"=> bcdiv($num,$balance[$currency],4),//重仓比例
                'scene'=>$scene,// 1普通场 2包赔场
                'period'=>$period,//周期60 120
                'direct'=>$direct,// 1涨 2跌
                'should_settle_time'=>$should_settle_time,
                'created_at'=>$create_time,
                'updated_at'=>date('YmdHis',bcdiv(strval($start_time),strval(1000),0)),
                'date'=>date('Y-m-d',bcdiv(strval($start_time),strval(1000),0)),//这个函数需要秒 毫秒得÷1000
                'delay' => $is_delay, //延迟多久，秒
                'order_type'=> $order_type
            ];
            //扣除余额
            $subNum = bcmul((string)$data['num'],'-1',6);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($data['user_id'],$currency,$balance[$currency],$subNum,6,'秒合约交易跟单',$orderSn);
            if($res !== true){
                throw new \Exception('Asset failed');//资产失败
            }
            //插入队列
            $rel = (new SecondJob(['data'=>$data]))->dispatch();
            if(!$rel){
                throw new \Exception('Msg failed');//消息失败
            }
            $data['should_settle_time'] = strval($data['should_settle_time'] + 0);
            //缓存老师数据
            if($userId == $teacher_id && !$is_malice){
                $teacher_keys = "teacher_data_exchange_{$teacher_id}";
                $this->getCache()->set($teacher_keys,json_encode($data),120);
            }
            return $data;
        } catch(\Throwable $e){
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[跟单下单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 跟单
     */
    public function coping($userId,$scene,$configService){
        $teacher_id =  $configService->value('teacher_scene_'. $scene);//获取该场次带单老师ID
        $teacher_keys = "teacher_data_exchange_{$teacher_id}";
        $teacher_data =$this->getCache()->get($teacher_keys);
        if(!$teacher_data){
            throw new AppException('second_not_data',400);//暂无数据可以跟单
        }
        $teacher_data = json_decode($teacher_data,true);
        $today_data = date("Y-m-d");
        $coping__keys = "coping_data_exchange_{$today_data}_{$userId}_{$teacher_data['should_settle_time']}";
        $coping_data =$this->getCache()->get($coping__keys);
        if($coping_data){
            throw new AppException('second_coping_data',400);//该时间已跟单过了
        }
        $data = $this->create($userId,$teacher_data['market'],$teacher_data['direct'],$teacher_data['period'],$teacher_data['num']);
        if($data === false){
            throw new AppException('second_error',400);//暂无数据可以跟单
        }
        //设置下单缓存
        $this->getCache()->set($coping__keys,$teacher_data['should_settle_time'],172800);
        return true;

    }

    /**
     * 虚拟
     */
    public function found($userId,$market,$direct,$period,$num){
        $start_time = bcsub($this->get_millisecond(),"60000",0);
        //查看交易对状态
        $second = $this->app(SysSecondService::class)->searchApi($market);
        if(empty($second) || $second['status'] ==0){
            throw new AppException('second_card_error',400);//交易不存在
        }
        if(!in_array($period,$second['trade_period_arr'])){
            throw new AppException('second_period_error',400);//周期不存在
        }
        /*禁止交易时间*/
        $next_m_s = date('H:i',strtotime('-1 minute'));
        /** @var  $ConfigService ConfigService*/
        $configService= $this->app(SysConfigService::class);
        // 检验获取场次校验
        $scene = $this->checkScene($next_m_s,$configService);
        if($scene==0){
            throw new AppException('second_time_off',400);//当前时间场次未开放购买
        }
        if(!$this->checkSceneNum($num,$scene,$configService)){
            throw new AppException('second_num_error',400); //'下单数量错误：' . $min_num . '~' . $max_num
        }
        // 检验K线数据  PS有没有当前时间点的K线数据  没有就获取上一分钟的数据
        $next_m =  strtotime(date('Y-m-d H:i:00',strtotime('-1 minute')));//当前分钟数据
        $kline_key = implode(':',[$second['market'],'1min']);
        $kline = $this->checkline($kline_key,$next_m);
        if(!$kline){
            throw new AppException('second_kline_error',400);//场次不存在，其实就是没K线数据
        }
        // 判断该用户是否恶意用户，如果是则将其下单时间放到02s  03s
        $is_malice = $this->app(UserService::class)->is_malice($userId);
        list($create_time,$should_settle_time,$is_delay) = $this->checkMalice($is_malice,$num,$period,$start_time,$configService);

        // 查看是否充足
        $currency = strtolower($second['currency']);
        $close_m =  strtotime(date('Y-m-d H:i:00'));//当前分钟数据
        $close_kline_key = implode(':',[$second['market'],'1min']);
        $close = $this->checklineClose($close_kline_key,$close_m);
        if(!$close){
            throw new AppException("k线数据不存在、{$create_time}、$should_settle_time",400);//当前时间场次未开放购买
        }
//        $info = [
//            'kline_key'=>$kline_key . ":".$next_m,
//            'close_kline_key'=>$close_kline_key . ":".$close_m,
//            'create_time'=>$create_time,
//            'should_settle_time'=>$should_settle_time,
//        ];
        $profit_status = 1;
        if($scene==1 && $profit_status==1){
            $config_rate = $configService->value("second_common_win");//普通盈
        }elseif($scene==2 && $profit_status==1){
            $config_rate = $configService->value("second_preserv_win");//包赔盈
        }elseif($scene==3 && $profit_status==1){
            $config_rate = $configService->value("second_preserv_win");//包赔盈
        }
        $profit = bcdiv(bcmul(strval($config_rate),strval($num),6),strval(100),6);
        try {
            //组装数据
            $orderSn = $this->makeOrdersn('T');
            //如果这一秒有真实盈利数据则插入真实，否则插入假数据
            $record = $this->logic->getQuery()->where('should_settle_time',$close_m)->where('second_id', $second['id'])
                ->where('profit_status',1)->where('settle_status',1)->first();
            if($record){
                $data = [
                    'user_id' => $record->user_id,
                    'order_sn' => $record->order_sn,
                    'second_id'=> $record->second_id,
                    'market'=> $record->market,
                    'symbol'=> $record->symbol,
                    'num'=>$record->num,
                    'time'=>$record->time,
                    'price'=>$record->price,
                    'scene'=>$record->scene,
                    'period'=>$record->period,
                    'direct'=>$record->direct,
                    'should_settle_time'=>$record->should_settle_time,
                    'settle_time' => $record->settle_time,
                    'settle_status' => $record->settle_status,
                    'settle_price' => $record->settle_price,
                    'profit_status' => $record->profit_status,
                    'profit' => $record->profit,
                    'created_at'=>$record->created_at,
                    'updated_at'=>$record->updated_at,
                    'date'=>$record->date,
                    'delay' => $record->delay,
                ];
            }else{
                $data = [
                    'user_id' => $userId,
                    'order_sn' => $orderSn,
                    'second_id'=> $second['id'],
                    'market'=> $second['market'],
                    'symbol'=> $currency,
                    'num'=>$num,
                    'time'=>$start_time,
                    'price'=>$kline['close'],
                    'scene'=>$scene,// 1普通场 2包赔场
                    'period'=>$period,//周期60 120
                    'direct'=>$direct,// 1涨 2跌
                    'should_settle_time'=>$should_settle_time,
                    'settle_time' => $should_settle_time,
                    'settle_status' => 1,
                    'settle_price' => $close['close'],
                    'profit_status' => $profit_status,
                    'profit' => $profit,
                    'created_at'=>$create_time,
                    'updated_at'=>date('YmdHis',bcdiv(strval($start_time),strval(1000),0)),
                    'date'=>date('Y-m-d',bcdiv(strval($start_time),strval(1000),0)),//这个函数需要秒 毫秒得÷1000
                    'delay' => $is_delay //延迟多久，秒
                ];
            }

            //组装1分钟内数据
            $cache_key ="{$market}:fount:1min:{$close_m}";
            $lists =$this->getCache()->get($cache_key);
            if($lists){
                $listData =json_decode($lists,true);
                $listData[] = $data;
                $this->getCache()->set($cache_key,json_encode($listData),600);
            }else{
                $listData[] = $data;
                $this->getCache()->set($cache_key,json_encode($listData),600);
            }
            return $data;
        } catch(\Throwable $e){
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[虚拟下单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*检查交易时间*/
    public function checkTime($second,$time){
        if($second['trade_forbid'] && strlen($second['trade_forbid'])==11){
            $arr = explode('-', $second['trade_forbid']);
            if(count($arr)==2){
                if ($arr[1] < $arr[0]) {
                    if ($time>=$arr[0]) {
                        return false;
                    } else {
                        if ($time<=$arr[1]) {
                            return false;
                        }
                    }
                } else {
                    if($time>=$arr[0]&&$time<=$arr[1]){
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /*一分钟内同一币种禁止超过3单*/
    public function checkOrderNums($userId,$second,$configService){
        $current_day = strtotime(date('Y-m-d'));
        $current_minute = strtotime(date('Y-m-d H:i:00'));
        $order_anti_shake_key = 'order_num:' . $current_day . ':' . $second['market'] . ':' . $current_minute . ':' . $userId;
        $order_num = $this->getCache()->get($order_anti_shake_key)??0;
        $order_num = intval($order_num);
        $second_down_user = explode('@',$configService->value("second_whilte_account"));
        if ($order_num >= 3 && !in_array($userId,$second_down_user)) {
            return  false;
        }
        $order_num++;
        $this->getCache()->set($order_anti_shake_key, $order_num, $current_minute);
        return true;
    }
    /*校验获取场次数据*/
    public function checkScene($now_m_s ,$configService){
        $w = date('w');
        $scene = 3;
        $putong_time = explode('@', $configService->value('second_common_time'));//普通场时间 00 - 12:00 赢40% 亏100%
        $peifu_time = explode('@', $configService->value('second_preserv_time'));//包赔场时间 12:00 - 24：00   赢5% 亏10%
        if($putong_time && count($putong_time)==2){
            if($now_m_s>=$putong_time[0] && $now_m_s<$putong_time[1]){
                $scene = 1;//普通场
            }
        }
        if($peifu_time && count($peifu_time)==2){
            if($now_m_s>=$peifu_time[0] && $now_m_s<$peifu_time[1]){
                $scene = 2;//包赔场
            }
        }
        if ($w == 6) { // 星期六
            if($peifu_time && count($peifu_time)==2){
                if($now_m_s>=$peifu_time[0] && $now_m_s<$peifu_time[1]){
                    $scene = 2;//包赔场
                } else {
                    $scene = 3;
                }
            } else {
                $scene = 3;
            }
        }
        if ($w == 0) { // 星期日
            $scene = 3;
        }

        return $scene;

    }

    /* 检验注册赠送金下单权限*/
    public function checkRegisLock($userId,$isGet=1){
        $key = "second_regis_lock_".$userId;
        if($isGet == 2){
            return $this->getCache()->get($key);
        }elseif($isGet == 3){
            return $this->getCache()->delete($key);
        }else{
            $this->getCache()->set($key,$userId,86400);
            return $userId;
        }
    }

    /*校验获取场次数据*/
    public function checkSceneNum($num,$scene,$configService){
        if($scene == 0){
            return false;
        }
        if($scene == 1){
            $num_limit =  explode('@', $configService->value('second_common_num'));//普通场数量限制
        }elseif($scene == 2){
            $num_limit =  explode('@', $configService->value('second_preserv_num'));//包赔场数量限制
        }elseif($scene == 3){
            $num_limit =  explode('@', $configService->value('no_second_common_num'));//非带单时段数量限制
        }
        if($num<$num_limit[0] || $num>$num_limit[1]){
            return false;
        }
        return true;
    }
    /* 检验K线数据  PS有没有当前时间点的K线数据  没有就获取上一分钟的数据*/
    public function checkline($kline_key,$now_m){
        $key = $kline_key.':'.$now_m;
        $kline = $this->app(ChannelRecordData::class)->all($key);
        if(!$kline){
            $last_m = strtotime(date('Y-m-d H:i:00',($now_m - 60)));//上一分钟数据
            $key = $kline_key.':'.$last_m;
            $kline = $this->app(ChannelRecordData::class)->all($key);
        }
        return $kline;
    }

    /*校验规避恶意用户 ，如果是则将其下单时间放到02s  03s*/
    public function checkMalice($is_malice, $num, $period, $start_time ,$configService){
        $blast_amount =  $configService->value('blasting_amount');//定向爆破金额
        $current_time = bcdiv(strval($start_time),strval(1000),0);
        $second_num = date('s', $current_time);
        if ($is_malice) { // 黑名单
            /**
             * 2024-11-17 备注，如果要开启黑名单功能
             *  1、需要把金额判断去掉
             *  2、把58秒改为57秒
             *  3、elseif去掉
             *  4、if判断中加入如下代码
             *  5、前端需要配合执行这个延迟操作
             *
                // 判断当前时间的秒，57、58、59，计算到下一分钟01的秒差
                if ($second_num == 57) {
                $is_delay = 4;
                } else if ($second_num == 58) {
                $is_delay = 3;
                } else if ($second_num == 59) {
                $is_delay = 2;
                } else if ($second_num == 00) {
                $is_delay = 1;
                } else {
                $is_delay = 0;
                }
             *
             */
            if ($num >= $blast_amount && $second_num >= '58' && $second_num <= '59') { // 要设下一分钟的02  03s
                $create_time = date('Y-m-d H:i:03', strtotime('1 minute'));
                $should_settle_time = bcadd(strval($period*1000),strval(strtotime($create_time) * 1000),6);//应结算时间
                $is_delay = 180;
            } elseif ($num >= $blast_amount && ($second_num == '00' || $second_num == '01')) {
                $create_time = date('Y-m-d H:i:03');//这个函数需要秒 毫秒得÷1000
                $should_settle_time = bcadd(strval($period*1000),strval(strtotime($create_time) * 1000),6);//应结算时间
                $is_delay = 180;
            } else {
                $create_time = date('Y-m-d H:i:s',bcdiv(strval($start_time),strval(1000),0));//这个函数需要秒 毫秒得÷1000
                $should_settle_time = bcadd(strval($period*1000),strval($start_time),6);//应结算时间
                $is_delay = 0;
            }
        } else {
            $second_whilte_flag =  $configService->value('second_whilte_flag');//白名单开关
            if ($second_whilte_flag && ($second_num == '01' || $second_num == '02')) {
                $create_time = date('Y-m-d H:i:00',bcdiv(strval($start_time),strval(1000),0));//这个函数需要秒 毫秒得÷1000
                $should_settle_time = bcadd(strval($period*1000),strval(strtotime($create_time) * 1000),6);//应结算时间
            } else {
                $create_time = date('Y-m-d H:i:s',bcdiv(strval($start_time),strval(1000),0));//这个函数需要秒 毫秒得÷1000
                $should_settle_time = bcadd(strval($period*1000),strval($start_time),6);//应结算时间
            }
            $is_delay = 0;
        }
        return [$create_time,$should_settle_time,$is_delay];
    }

    /* 获取结算K线数据，*/
//    public function checklineClose($kline_key,$close_m){
//        $key = $kline_key.':'. strtotime(date('Y-m-d H:i:00',intval($close_m)));
//        $kline = $this->app(ChannelRecordData::class)->all($key);
//        return $kline;
//    }
    public function checklineClose($kline_key,$close_m){
        $close_s =  strtotime(date('Y-m-d H:i:00',intval($close_m)));
        $key = $kline_key.':'. $close_s . ":". intval($close_m);
        //$this->logger('[获取结算K线数据01]','error')->info(json_encode(['分钟'=>$close_s,'秒级'=>$close_m,'key'=>$key],JSON_UNESCAPED_UNICODE));
        $kline = $this->app(ChannelRecordData::class)->all($key);
        if(!$kline){
            for ($i = 0; $i < 30; $i++) {
                $close_next_time =  intval($close_m)-$i;
                $close_next =  strtotime(date('Y-m-d H:i:00',  $close_next_time));
                $key = $kline_key.':'. $close_next . ":". $close_next_time;
                //$this->logger('[获取结算K线数据02]','error')->info(json_encode(['分钟'=>$close_next,'秒级'=>$close_next_time,'key'=>$key],JSON_UNESCAPED_UNICODE));
                $kline = $this->app(ChannelRecordData::class)->all($key); // code...
                if($kline){
                    break;
                }
            }
        }
        if(!$kline){//秒级没有-拿分钟线
            $key = $kline_key.':'. strtotime(date('Y-m-d H:i:00',intval($close_s)));
            $kline = $this->app(ChannelRecordData::class)->all($key);
        }
        if(!$kline){//秒级没有-分钟没有-拿上分钟线
            $key = $kline_key.':'. strtotime(date('Y-m-d H:i:00',intval($close_s - 60)));
            $kline = $this->app(ChannelRecordData::class)->all($key);
        }
        //$this->logger('[获取结算K线数据03]','error')->info(json_encode($kline,JSON_UNESCAPED_UNICODE));
        return $kline;
    }

    /* 获取对应场次老师数据*/
    public function checkTeacher($market,$create_time,$should_settle_time,$kprice,$start_time,$scene,$period,$direct,$configService){
        $is_teacher = false;
        $teacher_id =  $configService->value('teacher_scene_'. $scene);//获取该场次带单老师ID
        $teacher_keys = "teacher_data_exchange_{$teacher_id}";
        $teacher_data =$this->getCache()->get($teacher_keys);
        //老师数据不存在原样返回
        if(!$teacher_data){
            return [$is_teacher,$create_time,$should_settle_time,$kprice];
        }
        $teacher_data = json_decode($teacher_data,true);
        //不是和老师相同交易、方向、周期的原样返回
        if($market != $teacher_data['market'] || $scene != $teacher_data['scene'] || $period != $teacher_data['period'] || $direct != $teacher_data['direct']){
            return [$is_teacher,$create_time,$should_settle_time,$kprice];
        }
        // 下单时间和老师时间间距大于3700毫秒，原样返回
        $gaping = bcsub((string)$start_time,(string)$teacher_data['time'],0);
        if(-700 >$gaping || $gaping > 3300){
            return [$is_teacher,$create_time,$should_settle_time,$kprice];
        }
        //$is_teacher是否跟单、$create_time
        $is_teacher = true;$create_time = $teacher_data['created_at'];
        $should_settle_time = $teacher_data['should_settle_time'];$kprice = $teacher_data['price'];
        return [$is_teacher,$create_time,$should_settle_time,$kprice];
    }

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
            $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->symbol,$balance[$order->symbol],$order->num,7,'秒合约交易撤单',$order->id);
            if($res !== true){
                throw new \Exception('Asset failed');//资产失败
            }

            Db::commit();
            //更新业绩
           return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[跟单撤单]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*动态统计*/
    public function quicken($order){
        Db::beginTransaction();
        try {
            //更新奖金-推广
            if($order->reward_type == 1){
                $dnamic = $this->app(UserSecondQuickenService::class)->getQuery()->where('user_id',$order->user_id)->where("reward_type",1)->where("settle_time",'>',0)->sum("reward");
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['dnamic'=>$dnamic]);
                //更新奖金-团队
            }elseif($order->reward_type == 2){
                $groups = $this->app(UserSecondQuickenService::class)->getQuery()->where('user_id',$order->user_id)->where("reward_type",2)->where("settle_time",'>',0)->sum("reward");
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['groups'=>$groups]);
            }
            $this->app(UserSecondQuickenService::class)->getQuery()->where('id',$order->id)->update(['quicken_time'=>time()]);
            Db::commit();
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[奖金统计]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*静态 盈利结算*/
    public function income($order){
        if ($order->settle_status > 0 ) {
            return false;
        }

        $configService= $this->app(SysConfigService::class);
        //获取K线
        $should_settle_time = bcdiv(strval($order->should_settle_time),'1000',0);// 毫秒->秒

        $kline_key = implode(':',[$order->market,'1min']);
        $close = $this->checklineClose($kline_key,$should_settle_time);
        //$this->logger('[获取结算K线数据00]','error')->info(json_encode(['订单'=>$order->id,'price'=>$close,'结算秒'=>$should_settle_time,'结算毫秒'=>$order->should_settle_time],JSON_UNESCAPED_UNICODE));
        if(!$close){
            return false;
        }

        //判断涨跌
        $update['settle_price'] = $close['close'];//结算价
        $price = $order->price;//用户买入时结算价
        $direct = $order->direct;//用户买的涨跌 1涨 2跌
        if($close['close']>$price){
            $k_direct = 1; //涨
        }elseif($close['close']<$price){
            $k_direct = 2; //跌
        }else{
            $k_direct = 0; //平
        }
        //$direct 不可能为0只会是1 2  买到相同方向就是盈  否则就是亏
        if($k_direct==0){
            $profit_status=3;//平
        }elseif($direct==$k_direct){
            $profit_status=1;
        }else{
            $profit_status=2;
        }
        $update['profit_status']=$profit_status;//盈亏状态  1盈 2亏 3平
        //获取对应结算比例
        $scene = $order->scene;//交易类型 1普通场 2包赔场
        if($profit_status==3){
            $config_rate = 0;//平 没有盈亏
        }else{
            if($scene==1 && $profit_status==1){
                $config_rate = $configService->value("second_common_win");//普通盈
            }elseif($scene==1 && $profit_status==2){
                $config_rate = $configService->value("second_common_lose");//普通亏
            }elseif($scene==2 && $profit_status==1){
                $config_rate = $configService->value("second_preserv_win");//包赔盈
            }elseif($scene==2 && $profit_status==2){
                $config_rate = $configService->value("second_preserv_lose");//包赔亏
            }elseif($scene==3 && $profit_status==1){
                $config_rate = $configService->value("no_second_common_win");//非带单时段盈
            }elseif($scene==3 && $profit_status==2){
                $config_rate = $configService->value("no_second_common_lose");//非带单时段亏
            }else{
                $config_rate = 0;
            }
        }
        //本金 * 盈亏比例% = 盈亏金额
        $profit = bcdiv(bcmul(strval($config_rate),strval($order->num),6),strval(100),6);
        $update['rate'] = $config_rate;
        $update['profit']=$profit;//盈亏金额
        $update['settle_status']=1;//已结算
        $update['settle_time']=$this->get_millisecond();//结算时间
        //体验期10天或者先达到30u收益，则体验结束,执行锁仓体验金
        //$is_types = $this->app(UserService::class)->find($order->user_id);
        //$profitTotal = 0;
        //if($is_types->is_lock > 0 && $profit_status == 1){
            //$income_lock = $this->logic->getQuery()->where('user_id',$order->user_id)->where('settle_status',1)->where('profit_status',1)->sum('profit');
            //$deficit_lock = $this->logic->getQuery()->where('user_id',$order->user_id)->where('settle_status',1)->where('profit_status',2)->sum('profit');
            //$profitTotal = bcsub((string)$income_lock,(string)$deficit_lock,6);
            //$profitTotal = bcadd($profitTotal,(string)$profit,6);
            //if($profitTotal > 0){
                //$profitlock = bcsub('30',$profitTotal,6);
                //if($profitlock > 0){//未超出，直接原始奖金
                    //$update['profit'] = $profit;
                //}else{             //已超出，扣除超出部分（超出部分为负值，用原始奖金相加就是真实奖金）
                    //$update['profit'] = bcadd($profitlock,$profit,6);
                //}
            //}
        //}
        //
        //订单类型重置-未充值
        if($order->order_type == 0){
            $userCount = $this->app(UserCountService::class)->findByUid($order->user_id);
            if(0>=$userCount->recharge  && 0 >=$userCount->recharge_sys ){
                $update['order_type']=2;//未充值
            }
        }

        if($update['profit_status']==1){
            //盈
            $update['capital'] = bcadd(strval($order->num),strval($update['profit']),6);//本金 + 亏损金额 = 剩余金额
        }elseif($update['profit_status']==2){
            //亏
            $update['capital'] = bcsub(strval($order->num),strval($update['profit']),6);//本金 - 亏损金额 = 剩余金额
        }elseif($update['profit_status']==3){
            //平
            $update['capital'] = $order->num;//本金  = 剩余金额
        }

        Db::beginTransaction();
        try {
            $res = $this->logic->getQuery()->where(['id'=>$order->id,'settle_status'=>0])->update($update);
            if (!$res) {
                throw new \Exception('结算失败');
            }
            $balance = $this->app(UserBalanceService::class)->findByUid($order->user_id);
            if($update['capital'] > 0){
                $rel = $this->app(UserBalanceService::class)->rechargeTo($order->user_id, 'usdt', $balance['usdt'], $update['capital'], 9, '秒合约平仓结算', $order->id);
                if ($rel !== true) {
                    throw new \Exception('更新资产失败');
                }
            }
            //$lock_num = bcadd((string)$update['capital'], (string)$balance['usdt'],6);
            //if($profitTotal >= 30 && $lock_num >= 330){
                //$this->checkRegisLock($order->user_id,1);
                //$this->app(UserLockedOrderService::class)->create(['user_id'=>$order->user_id,'order_type'=>1,'order_num'=>$lock_num]);
            //}

            Db::commit();
            //更新今日奖金
            $income_today = $this->logic->getQuery()->where('user_id',$order->user_id)->where('settle_status',1)->where('profit_status',1)->whereDate('created_at',date("Y-m-d"))->sum('profit');
            $deficit_today = $this->logic->getQuery()->where('user_id',$order->user_id)->where('settle_status',1)->where('profit_status',2)->whereDate('created_at',date("Y-m-d"))->sum('profit');
            $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['income_today'=>$income_today,'deficit_today'=>$deficit_today]);

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
        if ($order->settle_status_income > 0 ) {
            return false;
        }
        Db::beginTransaction();
        try {
            $res = $this->logic->getQuery()->where(['id'=>$order->id,'settle_status'=>1])->update(['settle_status_income'=>1]);
            if (!$res) {
                throw new \Exception('结算失败');
            }
            if( $order->order_type == 0){
                $dnamic_time = 0 ;$groups_time=0;
            }elseif( $order->order_type == 1){
                $dnamic_time = time() ;$groups_time=time();
            }elseif( $order->order_type == 2){
                $dnamic_time = time() ;$groups_time=time();
            }elseif( $order->order_type == 3){
                $dnamic_time = time() ;$groups_time=time();
            }elseif( $order->order_type == 4){
                $dnamic_time = 0 ;$groups_time=0;
            }

            $record = [
                'user_id'=>$order->user_id,
                'order_id'=>$order->id,
                'second_id'=>$order->second_id,
                'total'=>$order->num,
                'symbol'=>'usdt',
                'rate'=>$order->rate,
                'reward'=> $order->profit,//今日收益代币-盈亏金额
                'reward_type'=> $order->profit_status,//1盈利  2 亏损 3平
                'order_type'=> $order->order_type,//0普通，1体验，2赠送金订单  3充值但小于1000 4充值大于1000
                'dnamic_time'=> $dnamic_time,//体验号\赠送金,不结算
                'groups_time'=> $groups_time,//体验号\赠送金,不结算
                'reward_time'=>time(),// 毫秒->秒
            ];
            $this->app(UserSecondIncomeService::class)->getQuery()->insert($record);
            //更新奖金
            Db::commit();
            $income = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',1)->sum('reward');
            $deficit = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$order->user_id)->where('reward_type',2)->sum('reward');
            $this->app(UserRewardService::class)->getQuery()->where('user_id',$order->user_id)->update(['income'=>$income,'deficit'=>$deficit]);
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[盈利结算]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /*静态 盈利统计*/
    public function incomeSettleExtend($userId,$type=1,$start=0,$end=0){

        try {

            //昨日盈亏
            if($type == 1){
                //$yesterday = Carbon::yesterday();$startDay = $yesterday->startOfDay()->timestamp; $endDay= $yesterday->endOfDay()->timestamp;
                $income_yestoday = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$userId)->where('reward_type',1)->where('reward_time','>=' ,$start)
                    ->where('reward_time',"<",$end)->sum('reward');
                $deficit_yestoday = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$userId)->where('reward_type',2)->where('reward_time','>=' ,$start)
                    ->where('reward_time',"<",$end)->sum('reward');
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$userId)->update(['income_yestoday'=>$income_yestoday,'deficit_yestoday'=>$deficit_yestoday]);

            }
            //上周盈亏
            if($type == 2){
                //$lastweek = Carbon::now();$startWeek = $lastweek->startOfWeek()->subWeek()->timestamp; $endWeek = $lastweek->endOfWeek()->timestamp;
                $income_week = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$userId)->where('reward_type',1)->where('reward_time','>=' ,$start)
                    ->where('reward_time',"<",$end)->sum('reward');
                $deficit_week = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id',$userId)->where('reward_type',2)->where('reward_time','>=' ,$start)
                    ->where('reward_time',"<",$end)->sum('reward');
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$userId)->update(['income_week'=>$income_week,'deficit_week'=>$deficit_week]);

            }

            //上月盈亏
            if($type == 3) {
                //$lastmonth = Carbon::now();$startMonth = $lastmonth->startOfMonth()->subMonth()->timestamp;$endMonth = $lastmonth->endOfMonth()->timestamp;
                $income_month = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id', $userId)->where('reward_type', 1)->where('reward_time', '>=', $start)
                    ->where('reward_time', "<", $end)->sum('reward');
                $deficit_month = $this->app(UserSecondIncomeService::class)->getQuery()->where('user_id', $userId)->where('reward_type', 2)->where('reward_time', '>=', $start)
                    ->where('reward_time', "<", $end)->sum('reward');
                $this->app(UserRewardService::class)->getQuery()->where('user_id',$userId)->update(['income_month'=>$income_month,'deficit_month'=>$deficit_month]);

            }
            return true;
        } catch(\Throwable $e){
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[盈利结算]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /* 动态 二代流水奖 - 预算*/
    public function dnamic($orderIncome,$dnamicRate){
        if ($orderIncome->dnamic_time > 0) {
            throw new AppException('订单已完成',400);
        }
        //上2代
        $parentIds = $this->app(UserRelationService::class)->getParent($orderIncome->user_id,2);
        $detailes = [];
        Db::beginTransaction();
        try {
            //1、有交易，算有效
            //2、净入金 = 充值 - 提现
            //3、成为新等级的那一天，30天有效期，不满足级别考核，降级，30天后成为永久级别。。
            //4、级别考核3000U为伞下净业绩 = 净入金 = 充值 - 提现
            foreach ($parentIds as $key=>$pid){

                $userExtend = $this->app(UserExtendService::class)->findByUid($pid);
                if(0 >= $userExtend->level){
                    $detailes[] = "用户-".$pid."，无级别";
                    continue;
                }
                //计算奖金
                if($userExtend->level == 1){
                    $rate = $dnamicRate[0];
                    $rewardUsdt = bcmul((string)$orderIncome->total, (string)$dnamicRate[0]/100,6);
                }elseif($userExtend->level >= 2){
                    $rate = $dnamicRate[1];
                    $rewardUsdt = bcmul((string)$orderIncome->total, (string)$dnamicRate[1]/100,6);
                }else{
                    $rate = 0;
                    $rewardUsdt = 0;
                }

                if($rewardUsdt > 0){
                    $record = [
                        'user_id'=>$pid,
                        'income_id'=>$orderIncome->id,
                        'second_id'=>$orderIncome->second_id,
                        'target_id'=>$orderIncome->user_id,
                        'total'=>$orderIncome->total,
                        'symbol'=>'usdt',
                        'rate'=>$rate,
                        'reward'=> $rewardUsdt,//今日收益
                        'reward_type'=> 1,//动态
                        'reward_time'=>time(),
                    ];
                    $this->app(UserSecondQuickenService::class)->getQuery()->insert($record);
                }
                $detailes[] = "用户-".$pid."，已达标，奖金：".$rewardUsdt;
            }
            $this->app(UserSecondIncomeService::class)->getQuery()->where(['id'=>$orderIncome->id,'dnamic_time'=>0])->update(['dnamic_time'=>time()]);
            Db::commit();
            $this->logger('[推广动态]','robot')->info(json_encode($detailes,JSON_UNESCAPED_UNICODE));
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[推广动态]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /* 动态 二代流水奖 - 结算 日结，过12点结算昨天的*/
    public function dnamicSettle($orderDnamic){
        if ($orderDnamic->settle_time > 0) {
            throw new AppException('订单已完成',400);
        }
        $detailes = [];
        //昨日是否有交易 - 获取最新一笔交易时间，来判断
        $recordTime = date('Y-m-d', strtotime('-1 day'));
        $counts = $this->app(UserSecondService::class)->getQuery()->where('user_id',$orderDnamic->user_id)->whereDate('date',$recordTime)->count();
        if(0 >= $counts){
            $detailes[] = "用户-".$orderDnamic->user_id."，{$recordTime}无交易";
            $this->app(UserSecondQuickenService::class)->getQuery()->where(['id'=>$orderDnamic->id,'settle_time'=>0])->update(['settle_time'=>time()]);
            $this->logger('[推广动态]','robot')->info(json_encode($detailes,JSON_UNESCAPED_UNICODE));
            return true;
        }
        Db::beginTransaction();
        try {
            $remark = '来源：用户'.$orderDnamic->target_id.'交易流水动态奖金';
            $balance = $this->app(UserBalanceService::class)->findByUid($orderDnamic->user_id);
            $rel = $this->app(UserBalanceService::class)->rechargeTo($orderDnamic->user_id,'usdt',$balance['usdt'],$orderDnamic->reward,10,$remark,$orderDnamic->id,$orderDnamic->target_id);
            if($rel !== true){
                throw new \Exception('更新资产失败');
            }
            $this->app(UserSecondQuickenService::class)->getQuery()->where(['id'=>$orderDnamic->id,'settle_time'=>0])->update(['settle_time'=>time()]);
            Db::commit();
            $this->logger('[推广动态]','robot')->info(json_encode($detailes,JSON_UNESCAPED_UNICODE));
            return true;
        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[推广动态]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    /*团队 二代外流水奖 V3用户 - 预算*/
    public function groupsCompute($userId,$look=false){
        $detailes = [];$isGroups = true;$orderTotal=0;
        $childs = $this->app(UserRelationService::class)->getChild($userId);
        $maxIds = $this->app(UserCountService::class)->getMaxIds($childs);//大区用户ID
        $now = Carbon::now(); $start = $now->startOfWeek()->subWeek()->timestamp; $ends = $now->endOfWeek()->timestamp;
        if(0>=$maxIds){
            $isGroups = false;
            $detailes[] = "团队奖励[用户-{$userId}，没有大区存在]";
        }
        if($isGroups == true) {
            $userIdsLevelOne = array_column($childs, 'uid');//直推用户
            $userIdsLevelTwo = $this->app(UserRelationService::class)->getQuery()->whereIn('pid', $userIdsLevelOne)->pluck('uid')->toArray();//二代用户
            $maxTeamIds = $this->app(UserRelationService::class)->getTeams($maxIds);//大区用户
            $maxTeamIds[] = $maxIds;
            //二代外流水ID
            if($look){
                $orderIds = $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('user_id', function ($query) use ($userId, $maxTeamIds, $userIdsLevelOne, $userIdsLevelTwo) {
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)', $userId)->whereNotIn('uid', $maxTeamIds)->whereNotIn('uid', $userIdsLevelOne)->whereNotIn('uid', $userIdsLevelTwo);
                })->where('reward_time', '>=', $start)->where('reward_time', '<=', $ends)->pluck('id');
                $detailes[] = "团队奖励[用户-{$userId}，一代 ".implode(",",$userIdsLevelOne)."二代 ".implode(",",$userIdsLevelTwo)."]";
            }else{
                $orderIds = $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('user_id', function ($query) use ($userId, $maxTeamIds, $userIdsLevelOne, $userIdsLevelTwo) {
                    return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)', $userId)->whereNotIn('uid', $maxTeamIds)->whereNotIn('uid', $userIdsLevelOne)->whereNotIn('uid', $userIdsLevelTwo);
                })->where('reward_time', '>=', $start)->where('reward_time', '<=', $ends)->where('groups_time', 0)->pluck('id');
            }


            // $orderIds = $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('user_id', function ($query) use ($userId ) {
            //     return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)', $userId);
            // })->whereNotIn('user_id', $maxTeamIds)->whereNotIn('user_id', $userIdsLevelOne)->whereNotIn('user_id', $userIdsLevelTwo)->where('reward_time', '>=', $start)->where('reward_time', '<=', $ends)->pluck('id');

            if (0 >= count($orderIds)) {
                $isGroups = false;
                $detailes[] = "团队奖励[用户-{$userId}，大区{$maxIds}, 暂无奖金或小区无交易]";
            }
            if ($isGroups == true) {
                //二代外总流水
                $orderTotal = $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('id', $orderIds)->sum('total');
                $detailes[] = "团队奖励[用户-{$userId}，大区{$maxIds}，本次总流水：{$orderTotal}]";
            }
        }
        return [$orderTotal,$start,$ends,$detailes];
    }

    /*团队 二代外流水奖 V3用户*/
    public function groups($userSecondKol,$groupRate,$number=0){
        if($userSecondKol->reward_time > 0){
            return true;
        }
        //计算奖金
        if($number > 0){
            $rewardUsdt = bcmul((string)$number, "1",6);
        }else{
            $rewardUsdt = bcmul((string)$userSecondKol->amount, "1",6);
        }
        if($rewardUsdt > 0){
            //发放奖金
            Db::beginTransaction();
            try {
                $res = $this->app(UserSecondKolService::class)->getQuery()->where('id',$userSecondKol->id)->update(['reward_time'=>time(),'reward'=>$rewardUsdt]);
                if(!$res){
                    throw new \Exception('更新日志失败');
                }
                $time = date('Y-m-d');
                $remark = "来源：本周{$time}团队奖励";
                $balance = $this->app(UserBalanceService::class)->findByUid($userSecondKol->user_id);
                $rel = $this->app(UserBalanceService::class)->rechargeTo($userSecondKol->user_id, 'usdt', $balance['usdt'], $rewardUsdt, 11, $remark);
                if ($rel !== true) {
                    throw new \Exception('更新资产失败');
                }
                $record = [
                    'user_id'=>$userSecondKol->user_id,
                    'income_id'=>0,
                    'second_id'=>0,
                    'target_id'=>0,
                    'total'=>$userSecondKol->total,
                    'symbol'=>'usdt',
                    'rate'=>$groupRate,
                    'reward'=> $rewardUsdt,//今日收益
                    'reward_type'=> 2,//团队
                    'reward_time'=>time(),
                    'settle_time'=>time(),
                ];
                $reb = $this->app(UserSecondQuickenService::class)->getQuery()->insert($record);
                if(!$reb){
                    throw new \Exception('插入日志失败');
                }
                $detailes[] = "团队奖励[用户-{$userSecondKol->user_id}，奖金为{$rewardUsdt}]";
                Db::commit();
            } catch(\Throwable $e){
                Db::rollBack();
                //写入错误日志
                $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
                $this->logger('[团队奖励]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
                return false;
            }
        }else{
            $this->app(UserSecondKolService::class)->getQuery()->where('id',$userSecondKol->id)->update(['reward_time'=>time(),'reward'=>$rewardUsdt]);
            $detailes[] = "团队奖励[用户-{$userSecondKol->user_id}，奖金为0]";
        }

        $this->app(UserSecondIncomeService::class)->getQuery()->whereIn('user_id',function ($query) use ($userSecondKol){
            return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$userSecondKol->user_id);
        })->where("groups_time",0)->update(['groups_time'=>time()]);
        $this->app(UserExtendService::class)->getQuery()->where('user_id',$userSecondKol->user_id)->update(['last_groups'=>0]);
        $this->logger('[团队奖励]','robot')->info(json_encode($detailes,JSON_UNESCAPED_UNICODE));
        return true;
    }

}