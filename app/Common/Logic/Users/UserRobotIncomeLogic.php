<?php


namespace App\Common\Logic\Users;


use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserRobotIncome;

class UserRobotIncomeLogic extends BaseLogic
{
    /**
     * @var UserRobotIncome
     */
    protected function getModel(): string
    {
        return UserRobotIncome::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_robot_income.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_robot_income.*', 'user.id as user_id', 'user.username');;

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

             return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use($where){

            return $query->where('order_id', $where['order_id'] );

        })->when(isset($where['ucard_id']) && $where['ucard_id'] !== '', function ($query)use($where) {

            return $query->where('ucard_id',$where['ucard_id']);

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