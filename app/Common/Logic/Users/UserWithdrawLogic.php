<?php


namespace App\Common\Logic\Users;

use Hyperf\DbConnection\Db;
use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserWithdraw;

class UserWithdrawLogic extends BaseLogic
{
    /**
     * @var UserWithdraw
     */
    protected function getModel(): string
    {
        return UserWithdraw::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){


        $query = $this->getQuery()->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use($where){

            return $query->where('order_id', $where['order_id']);

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id']);
            
        })->when(isset($where['user_ids']) && $where['user_ids'] !== '', function ($query) use($where){

            return $query->whereIn('user_id', $where['user_ids']);
            
        })->when(isset($where['top']) && $where['top'] !== '', function ($query) use($where){
            return $query->whereIn('user_id', function ($query) use($where){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$where['top']);
            });
        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {

            return $query->where('withdraw_status',$where['status'] );

        })->when(isset($where['address']) && $where['address'] !=='', function ($query)use($where) {

            return $query->where('bank_account','like', '%'. trim($where['address']).'%');

        })->when(isset($where['type']) && $where['type'] !=='', function ($query)use($where) {

            return $query->where('order_type',$where['type'] );

        })->when(isset($where['coinames']) && $where['coinames'] !=='', function ($query)use($where) {

            return $query->where('order_coin',$where['coinames']);

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('created_at','>=',$where['timeStart']);

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('created_at','<',$where['timeEnd']);

        })->orderBy('order_id', 'desc');

        return $query;

    }

}