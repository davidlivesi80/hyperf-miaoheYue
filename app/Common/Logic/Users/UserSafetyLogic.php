<?php
namespace App\Common\Logic\Users;


use App\Common\Model\Users\UserSafety;
use Upp\Basic\BaseLogic;



class UserSafetyLogic extends BaseLogic
{
    /**
     * @var UserSafety
     */
    protected function getModel(): string
    {
        return UserSafety::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_safety.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_safety.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['safety_id']) && $where['safety_id'] !== '', function ($query) use($where){

            return $query->where('safety_id', $where['safety_id'] );

        })->when(isset($where['order_sn']) && $where['order_sn'] !=='', function ($query)use($where) {

            return $query->where('order_sn',$where['order_sn'] );

        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {//其他状态

            return $query->where('status',$where['status'] );

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('buy_time','>=', $where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('buy_time','<',$where['timeEnd']);

        })->orderBy('id', 'desc');

        return $query;

    }

}