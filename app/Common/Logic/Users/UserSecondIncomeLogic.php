<?php


namespace App\Common\Logic\Users;


use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserSecondIncome;

class UserSecondIncomeLogic extends BaseLogic
{
    /**
     * @var UserSecondIncome
     */
    protected function getModel(): string
    {
        return UserSecondIncome::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_second_income.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_second_income.*', 'user.id as user_id', 'user.username');;

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

             return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['top']) && $where['top'] !== '', function ($query) use($where){
            return $query->whereIn('user_id', function ($query) use($where){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$where['top']);
            });
        })->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use($where){

            return $query->where('order_id', $where['order_id'] );

        })->when(isset($where['second_id']) && $where['second_id'] !== '', function ($query)use($where) {

            return $query->where('second_id',$where['second_id']);

        })->when(isset($where['reward_type']) && $where['reward_type'] !== '', function ($query) use($where){

            return $query->where('reward_type', $where['reward_type'] );

        })->when(isset($where['reward_types']) && $where['reward_types'] !== '', function ($query) use($where){

            return $query->whereIn('reward_type', $where['reward_types']);

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('reward_time','>=',strtotime($where['timeStart']));

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('reward_time','<',strtotime($where['timeEnd']));

        })->orderBy('id', 'desc');

        return $query;

    }

}