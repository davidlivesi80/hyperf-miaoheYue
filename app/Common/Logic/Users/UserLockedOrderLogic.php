<?php


namespace App\Common\Logic\Users;



use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserLockedOrder;

class UserLockedOrderLogic extends BaseLogic
{
    /**
     * @var UserLockedOrder
     */
    protected function getModel(): string
    {
        return UserLockedOrder::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){


        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){
            return $query->join('user', function ($join) use($where){
                $join->on('user_locked_order.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_locked_order.*', 'user.id as uid', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['order_type']) && $where['order_type'] !== '', function ($query) use($where){

            return $query->where('order_type', $where['order_type'] );

        });

        return $query->orderBy('id', 'desc');

    }

}