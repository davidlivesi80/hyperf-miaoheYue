<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserRadot;

class UserRadotLogic extends BaseLogic
{
    /**
     * @var UserRadot
     */
    protected function getModel(): string
    {
        return UserRadot::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_radot.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_radot.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['order_sn']) && $where['order_sn'] !=='', function ($query)use($where) {

            return $query->where('order_sn',$where['order_sn'] );

        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {

            return $query->where('status',$where['status'] );

        })->when(isset($where['statusIn']) && $where['statusIn'] !=='', function ($query)use($where) {

            return $query->whereIn('status',$where['statusIn'] );

        })->when(isset($where['robot_id']) && $where['robot_id'] !== '', function ($query)use($where) {

            return $query->where('robot_id',$where['robot_id']);

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('buy_time','>=', $where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('buy_time','<',$where['timeEnd']);

        })->orderBy('id', 'desc');

        return $query;

    }

}