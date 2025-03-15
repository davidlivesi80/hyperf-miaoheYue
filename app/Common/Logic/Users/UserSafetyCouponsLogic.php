<?php
namespace App\Common\Logic\Users;


use App\Common\Model\Users\UserSafetyCoupons;
use Upp\Basic\BaseLogic;


class UserSafetyCouponsLogic extends BaseLogic
{
    /**
     * @var UserSafetyCoupons
     */
    protected function getModel(): string
    {
        return UserSafetyCoupons::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['target_id']) && $where['target_id'] !== '', function ($query) use($where){

            return $query->where('target_id', $where['target_id'] );

        })->when(isset($where['order_type']) && $where['order_type'] !=='', function ($query)use($where) {

            return $query->where('order_type',$where['order_type'] );

        })->when(isset($where['order_sn']) && $where['order_sn'] !=='', function ($query)use($where) {

            return $query->where('order_sn',$where['order_sn'] );

        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {//其他状态

            return $query->where('status',$where['status'] );

        })->when(isset($where['statusIn']) && $where['statusIn'] !=='', function ($query)use($where) {//其他状态

            return $query->whereIn('status',$where['statusIn'] );

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('created_at','>=', $where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('created_at','<',$where['timeEnd']);

        })->orderBy('id', 'desc');

        return $query;

    }

}