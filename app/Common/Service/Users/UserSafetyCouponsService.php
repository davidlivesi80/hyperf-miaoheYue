<?php

namespace App\Common\Service\Users;


use App\Common\Model\Users\UserSafety;
use App\Common\Service\System\SysSafetyService;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserSafetyCouponsLogic;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;

class UserSafetyCouponsService extends BaseService
{
    use HelpTrait;

    /**
     * @var UserSafetyCouponsLogic
     */
    public function __construct(UserSafetyCouponsLogic $logic)
    {
        $this->logic = $logic;

    }

    /**
     * 查询构造
     */
    public function search(array $where, $with = [], $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->with($with)->paginate($perPage, ['*'], 'page', $page);

        return $list;

    }


    /**
     * 创建赠送--后台
     */
    public function create($userId,$targetId=0,$number = 1)
    {

        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userId,
                'order_sn' => $this->makeOrdersn('SP'),
                'target_id' => $targetId,
                'order_type' => 1,
                'number' => $number,
                "total" => 0,
                'status' => 0,//状态：2 赠送他人；1 买保险；0：未使用,可以用来赠送他人或买保险
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }

            Db::commit();
            //统计接收者有效数量
            $safety_coupons = $this->logic->getQuery()->where('user_id',$order->user_id)->where('status',0)->sum('number');
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_coupons'=>$safety_coupons]);
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[创建保险卷]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 赠送会员
     */
    public function found($userId,$targetId,$orderSn)
    {

        //只能转伞下
         $targetids = $this->app(UserRelationService::class)->getParent($userId);
         if(!in_array($targetId,$targetids)){
             throw new AppException('child_error',400);//不是伞下
         }
        $coupons = $this->logic->getQuery()->where('order_sn',$orderSn)->where('status',0)->where('user_id',$targetId)->first();
        if(!$coupons){
            throw new AppException('couposn_not',400);//保险卷不存在
        }

        Db::beginTransaction();
        try {
            //组装数据
            $record = [
                'user_id' => $userId,
                'order_sn' => $coupons->order_sn,
                'target_id' =>$targetId,
                'order_type' => 2,
                'number' => $coupons->number,
                "total" => $coupons->total,
                'status' => 0,//状态：2 赠送他人；1 买保险；0：未使用,可以用来赠送他人或买保险
            ];
            //创建
            $order = $this->logic->create($record);
            if(!$order){
                throw new \Exception('创建失败');
            }
            //更新卷数据
            if($this->logic->getQuery()->where('id',$coupons->id)->update(['status'=>2]) === false){
                throw new \Exception('赠送失败');//资产失败
            }

            Db::commit();
            //统计接收者有效数量
            $safety_coupons = $this->logic->getQuery()->where('user_id',$order->user_id)->where('status',0)->sum('number');
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->user_id)->update(['safety_coupons'=>$safety_coupons]);
            //统计赠送者有效数量
            $target_coupons = $this->logic->getQuery()->where('user_id',$order->target_id)->where('status',0)->sum('number');
            $this->app(UserExtendService::class)->getQuery()->where('user_id',$order->target_id)->update(['safety_coupons'=>$target_coupons]);

            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            //写入错误日志
            $error = ['file' => $e->getFile(), 'line' => $e->getLine(), 'msgs' => $e->getMessage()];
            $this->logger('[赠送保险卷]', 'error')->info(json_encode($error, JSON_UNESCAPED_UNICODE));
            return false;
        }
    }





    /**
     * 统计
     */
    public function counts(array $where)
    {
        $total = $this->logic->getQuery()->where('status',1)->sum('total');//累计赔付
        $today_amount = $this->logic->search($where)->where('status',1)->sum('amount');//应赔付
        $today_total = $this->logic->search($where)->where('status',1)->sum('total');//真实赔付
        $safety_total = $this->app(UserSafetyService::class)->getQuery()->where('status',1)->sum('total');//保险总额
        return compact('total','today_amount','today_total','safety_total');

    }


}