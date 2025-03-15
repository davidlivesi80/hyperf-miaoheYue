<?php


namespace App\Common\Logic\Users;


use App\Common\Model\Users\UserRobotQuicken;
use Upp\Basic\BaseLogic;

class UserRobotQuickenLogic extends BaseLogic
{
    /**
     * @var UserRobotQuicken
     */
    protected function getModel(): string
    {
        return UserRobotQuicken::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

             return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['target_id']) && $where['target_id'] !=='', function ($query)use($where) {

            return $query->where('target_id',$where['target_id'] );

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