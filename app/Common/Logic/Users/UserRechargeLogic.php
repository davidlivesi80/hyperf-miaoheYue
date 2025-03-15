<?php


namespace App\Common\Logic\Users;

use Hyperf\DbConnection\Db;
use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserRecharge;

class UserRechargeLogic extends BaseLogic
{
    /**
     * @var UserRecharge
     */
    protected function getModel(): string
    {
        return UserRecharge::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_recharge.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_recharge.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use($where){

            return $query->where('order_id', $where['order_id']);

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['user_ids']) && $where['user_ids'] !== '', function ($query) use($where){

            return $query->whereIn('user_id', $where['user_ids']);
            
        })->when(isset($where['top']) && $where['top'] !== '', function ($query) use($where){
            return $query->whereIn('user_id', function ($query) use($where){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$where['top']);
            });

        })->when(isset($where['address']) && $where['address'] !== '', function ($query) use($where){
            return $query->whereIn('order_id', function ($query) use($where){
                return $query->select('pid')->from('user_recharge_ex')->where('from','like', '%'. trim($where['address']).'%');
            });
        })->when(isset($where['coinames']) && $where['coinames'] !=='', function ($query)use($where) {

            return $query->where('order_coin',$where['coinames'] );

        })->when(isset($where['type']) && $where['type'] !=='', function ($query)use($where) {

            return $query->where('order_type',$where['type'] );

        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {

            return $query->where('recharge_status',$where['status'] );

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('created_at','>=',$where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('created_at','<',$where['timeEnd']);

        });

        return $query;

    }

}