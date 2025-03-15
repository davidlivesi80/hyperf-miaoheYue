<?php


namespace App\Common\Logic\Users;

use App\Common\Model\Users\UserPowerOrder;
use Upp\Basic\BaseLogic;

class UserPowerOrderLogic extends BaseLogic
{
    /**
     * @var UserPowerOrder
     */
    protected function getModel(): string
    {
        return UserPowerOrder::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_power_order.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_power_order.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['order_sn']) && $where['order_sn'] !=='', function ($query)use($where) {

            return $query->where('order_sn',$where['order_sn'] );

        })->when(isset($where['buy_type']) && $where['buy_type'] !=='', function ($query)use($where) {

            return $query->where('buy_type',$where['buy_type'] );

        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {

            return $query->where('status',$where['status'] );

        })->when(isset($where['paid']) && $where['paid'] !=='', function ($query)use($where) {

            return $query->where('pay_type',$where['paid']);

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('buy_time','>=', $where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('buy_time','<',$where['timeEnd']);

        })->orderBy('id', 'desc');

        return $query;

    }

}