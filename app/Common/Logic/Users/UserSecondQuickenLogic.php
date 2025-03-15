<?php


namespace App\Common\Logic\Users;


use App\Common\Model\Users\UserSecondQuicken;
use Upp\Basic\BaseLogic;

class UserSecondQuickenLogic extends BaseLogic
{
    /**
     * @var UserSecondQuicken
     */
    protected function getModel(): string
    {
        return UserSecondQuicken::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

             return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['target_id']) && $where['target_id'] !=='', function ($query)use($where) {

            return $query->where('target_id',$where['target_id'] );

        })->when(isset($where['second_id']) && $where['second_id'] !=='', function ($query)use($where) {

            return $query->where('second_id',$where['second_id'] );

        })->when(isset($where['reward_type']) && $where['reward_type'] !== '', function ($query) use($where){

            return $query->where('reward_type', $where['reward_type'] );

        })->when(isset($where['reward_types']) && $where['reward_types'] !== '', function ($query) use($where){

            return $query->whereIn('reward_type', $where['reward_types']);

        })->when(isset($where['settle_time']) && $where['settle_time']  !== '', function ($query) use($where){

            if($where['settle_time'] == 0){
                return $query->where('settle_time', 0);
            }elseif($where['settle_time'] > 0){
                return $query->where('settle_time','>', 0);
            }

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('reward_time','>=',strtotime($where['timeStart']));

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('reward_time','<',strtotime($where['timeEnd']));

        })->orderBy('id', 'desc');

        return $query;

    }

}