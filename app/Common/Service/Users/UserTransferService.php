<?php


namespace App\Common\Service\Users;

use App\Common\Service\System\SysGameService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserTransferLogic;
use Upp\Exceptions\AppException;

class UserTransferService extends BaseService
{
    /**
     * @var UserTransferLogic
     */
    public function __construct(UserTransferLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user'=>function($query){

            return $query->select('is_bind','email','mobile','username','id');

        },'target'=>function($query){

            return $query->select('is_bind','email','mobile','username','id');

        }])->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where){

        $list = $this->logic->search($where)->get();

        return $list;

    }


    /**

     * 添加-内转

     */

    public function create($userId,$data){

        $exists = $this->logic->getQuery()->where('user_id',$userId)->where('transfer_status',1)->exists();

        if ($exists) {
            throw new AppException('order_tip',400);//您有一笔订单,等待处理
        }

        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if(abs($data['number']) > $balance[strtolower($data['coin'])]){
            throw new AppException('insufficient_balance',400);
        }
        //检查用户
        $target = $this->app(UserService::class)->findWhere('username',$data['target']);
        if (!$target) {
            throw new AppException('target_error',400);//对方不存在
        }
        //必须有保单，有效用户
        // $counts = $this->app(UserCountService::class)->findByUid($userId);
        // if (0>= $counts->self) {
        //     throw new AppException('username_xiao',400);//不是有效用户
        // }
        // $timeIds = $this->app(UserRelationService::class)->getTeams($userId);
        // if(!in_array($target->id,$timeIds)){
        //     throw new AppException('username_childs',400);//不是伞下
        // }
        
        Db::beginTransaction();
        try {
            $record['order_sn']   =  $this->makeOrdersn('HZ');
            $record['user_id']   =  $userId;
            $record['target_id'] = $target->id;
            $record['order_type']   =   1;//1,内置，2外置
            $record['order_method']   =  2;//1,增加系统，减少系统
            $record['order_amount']   = $data['number'];
            $record['order_rate']   =  $data['rate'];
            $record['order_mone']   =  bcsub((string)$data['number'],bcmul((string)$data['number'],(string)($data['rate']/100),6),6);
            $record['order_coin']   =  strtolower($data['coin']);
            //创建
            $order = $this->logic->create($record);
            if($order){
                //减少自己
                $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_coin,$balance[$order->order_coin],-$order->order_amount,4,'用户划转到'.$data['target'],$order->order_id);
                if($res !== true) throw new \Exception( "更新失败2");
                //增加对方
                $targetBalance = $this->app(UserBalanceService::class)->findByUid($order->target_id);
                $res = $this->app(UserBalanceService::class)->rechargeTo($order->target_id,$order->order_coin,$targetBalance[$order->order_coin],$order->order_mone,4,'用户划转',$order->order_id);
                if($res !== true) throw new \Exception( "更新失败3");
            }

            //更新
            $this->logic->update($order->order_id,['transfer_status'=>2]);

            Db::commit();

            return true;

        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[互转交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }
    /**

     * 添加-外转

     */

    public function found($userId,$data){

        $exists = $this->logic->getQuery()->where('user_id',$userId)->where('transfer_status',1)->exists();

        if ($exists) {
            throw new AppException('订单已提交,正在处理',400);
        }

        $game = $this->app(SysGameService::class)->find($data['game_id']);
        if (!$game) {
            throw new AppException('游戏不存在',400);
        }

        //检查游戏用户
        $user = $this->app(UserService::class)->find($userId);
        $gameUser ['username'] = $user->username;
        $gameUser ['gamename'] = $game->name;
        $gameUser ['sign'] = $this->app(SysGameService::class)->makesign($gameUser);
        $uri = $game->url . "/api.php/index/checkuser";
        $target = $this->app(SysGameService::class)->requestGame($uri,$gameUser);
        if(!$target){
            throw new AppException('游戏角色不存在',400);
        }
        //判断余额
        $balance = $this->app(UserBalanceService::class)->findByUid($userId);
        if( $data['method'] == 2){
            if(  abs($data['number']) > $balance['blue']){
                throw new AppException('余额不足',400);
            }
        }else if( $data['method'] == 1){
            if( abs($data['number']) > $target['score']){
                throw new AppException('游戏余额不足',400);
            }
        }else{
            throw new AppException('参数错误',400);
        }

        Db::beginTransaction();

        try {
            $record['order_sn']   =  $this->makeOrdersn('HZ');
            $record['user_id']   =  $userId;
            $record['target_id'] = $target['id'];
            $record['order_type']   =  2;//1,内置，2外置
            $record['order_method']  =  $data['method'];//1,增加系统，减少系统
            $record['order_amount']  = $data['number'];
            $record['order_rate']   =  $data['rate'];
            $record['order_mone']   =  $data['number'];
            $record['order_coin']   =  strtolower('blue');
            //创建
            $order = $this->logic->create($record);
            if($order){
                $addbalan['username'] = $target['username'];
                $addbalan['score'] = $order->order_amount;
                $addbalan['type'] = $data['method'] == 2 ? 1 : 0;  //游戏1增加 2扣除
                $addbalan ['sign'] = $this->app(SysGameService::class)->makesign($addbalan);
                $uri = $game->url . "/api.php/index/addbalance";
                $rel = $this->app(SysGameService::class)->requestGame($uri,$addbalan);
                if($order['order_method'] == 2){
                    //减少系统
                    $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_coin,$balance[$order->order_coin],-$order->order_amount,6,'游戏划转',$order->order_id);
                    if($res !== true) throw new \Exception( "更新失败2");
                }elseif($order['order_method'] == 1){
                    //增加系统
                    $res = $this->app(UserBalanceService::class)->rechargeTo($order->user_id,$order->order_coin,$balance[$order->order_coin],$order->order_mone,6,'游戏划转',$order->order_id);
                    if($res !== true) throw new \Exception( "更新失败2");
                }else{
                    throw new \Exception( "参数错误");
                }
            }

            //更新
            $this->logic->update($order->order_id,['transfer_status'=>2]);
            Db::commit();

            return $rel['score'];

        } catch(\Throwable $e){
            Db::rollBack();
            //写入错误日志
            $error = ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[互转交易]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }

    }



}