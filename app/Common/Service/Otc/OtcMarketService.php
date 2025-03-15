<?php


namespace App\Common\Service\Otc;


use App\Common\Service\Users\UserBalanceService;
use App\Common\Service\Users\UserExtendService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Otc\OtcMarketLogic;
use Upp\Exceptions\AppException;

class OtcMarketService extends BaseService
{
    /**
     * @var OtcMarketLogic
     */
    public function __construct(OtcMarketLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllType()
    {

        return [

            ['id'=>1,'title'=>'外部应用'],

            ['id'=>2,'title'=>'内部应用']

        ];
    }

    public function getAllMethod()
    {

        return [

            ['id'=>1,'title'=>'无需跳转'],

            ['id'=>2,'title'=>'外部跳转'],

            ['id'=>3,'title'=>'内部跳转']

        ];

    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username'])->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function searchApi(array $where,$page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username'])->paginate($perPage,['*'],'page',$page);;

        return $list;
    }


    /**
     * 发布订单
     */
    //发布
    function publish($userId,$data){
        // 该用户提现状态
        if(!$this->app(UserExtendService::class)->findByUid($userId)['is_withdraw']){
            throw new AppException('提现权未开启，请联系管理员',400);
        }

        //挂单量
        $coins = $this->app(OtcCoinsService::class)->find($data['coin_id']);
        if(!$coins){
            throw new AppException('交易不存在',400);
        }
        if (0 >= $coins->enable ) {
            throw new AppException('交易已关闭',400);
        }
        if (0 >= $coins->limit_min_price || 0 >=  $coins->limit_max_price || 0 >=  $coins->limit_min_number || 0 >=  $coins->limit_max_number) {
            throw new AppException('交易未初始化',400);
        }
        if ($data['price'] < $coins->limit_min_price || $data['price'] > $coins->limit_max_price) {
            throw new AppException('价格区间：' . $coins['limit_min_price'] . ' - ' .$coins['limit_max_price'],400);
        }
        if ($data['number'] < $coins->limit_min_number || $data['number'] > $coins->limit_max_number) {
            throw new AppException('数量区间：' . $this->cus_floatval($coins->limit_min_number, 4) . ' - ' . $this->cus_floatval($coins->limit_max_number, 4),400);
        }
        //统计挂单量
        $count = $this->logic->getQuery()->where(['user_id'=>$userId,'finish_time'=>0,'side'=>$data['side']])->whereIn('status',[1,2])->count();
        if($count >= $coins->max_pub_num){
            throw new AppException('已超出最大单量',400);
        }
        //余额
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($data['number']) > $balance[strtolower($coins->coin_name)]){
            throw new AppException('余额不足',400);
        }
        $order_amount = bcmul((string)$data['number'],(string)$data['price'],6);
        Db::beginTransaction();
        try{
            //创建订单
            $data = [
                'order_sn'=> $this->makeOrdersn('OT'),
                'side'=> $data['side'],
                'user_id'=> $userId,
                'otc_coin_id'=> $coins->id,
                'otc_coin_name'=> strtolower( $coins->coin_name),
                'min_num'=> $data['number'],
                'max_num'=> $data['number'],
                'order_nums'=> $data['number'],
                'order_amount'=> $order_amount,
                'price'=> $data['price'],
                'publish_time' =>  date('Y-m-d H:i:s',time()),
                'run_time'  => date('Y-m-d H:i:s',time() + ($coins->max_pub_time * 60))
            ];
            $order = $this->logic->create($data);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //扣余额
            $res =  $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->otc_coin_name,$balance[$order->otc_coin_name],-$order->order_amount,20,'OTC挂单扣除',$order->id);
            if($res !== true){
                throw new \Exception('资产失败');
            }

            Db::commit();
            return $order;
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollback();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[OTC交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }

    //发布启用
    function enable($userId,$id){
        $publish = $this->logic->getQuery()->where(['id'=>$id,'user_id'=>$userId,'finish_time'=>0])->first();
        if (!$publish) {
            throw new AppException('该广告已被抢单',400);
        }
        if ($publish->status == 0) {
            throw new AppException('该广告已经下架',400);
        }
        if ($publish->status != 2) {
            throw new AppException('请先禁用广告',400);
        }

        $rasult = $this->logic->getQuery()->where(['id'=>$publish->id,'status'=>2,'finish_time'=>0])->update(['status'=>1]);
        if (!$rasult) {
            throw new AppException('启用失败!!',400);
        }

        return true;
    }
    //发布禁用
    function disabe($userId,$id){
        $publish = $this->logic->getQuery()->where(['id'=>$id,'user_id'=>$userId,'finish_time'=>0])->first();
        if (!$publish) {
            throw new AppException('该广告已被抢单',400);
        }
        if ($publish->status == 0) {
            throw new AppException('该广告已经下架',400);
        }
        if ($publish->status != 1) {
            throw new AppException('请先启用广告',400);
        }

        $rasult = $this->logic->getQuery()->where(['id'=>$publish->id,'status'=>1,'finish_time'=>0])->update(['status'=>2]);
        if (!$rasult) {
            throw new AppException('启用失败!!',400);
        }

        return true;

    }
    //发布撤销
    function remove($userId,$id){
        $publish = $this->logic->getQuery()->where(['id'=>$id,'user_id'=>$userId,'finish_time'=>0])->first();
        if (!$publish) {
            throw new AppException('该广告已被抢单',400);
        }
        if ($publish->status == 0) {
            throw new AppException('该广告已经下架',400);
        }
        if ($publish->status != 2) {
            throw new AppException('请先禁用广告',400);
        }

        Db::beginTransaction();
        try {

            $rasult = $this->logic->getQuery()->where(['id'=>$publish->id,'status'=>2,'finish_time'=>0])->update(['status'=>0]);
            if (!$rasult) {
                throw new \Exception("更新失败");
            }
            //返还代币
            $balance = $this->app(UserBalanceService::class)->findByUid($userId);
            $res =  $this->app(UserBalanceService::class)->rechargeTo($publish->user_id,$publish->otc_coin_name,$balance[$publish->otc_coin_name],$publish->order_amount,24,'OTC撤单返还',$publish->id);
            if($res !== true){
                throw new \Exception('资产失败');
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollback();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[OTC交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;
        }

    }


}