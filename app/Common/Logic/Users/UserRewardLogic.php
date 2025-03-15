<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserReward;

class UserRewardLogic extends BaseLogic
{
    /**
     * @var UserReward
     */
    protected function getModel(): string
    {
        return UserReward::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_reward.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_reward.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['types']) && $where['types'] != '', function ($query) use($where){
            return $query->whereNotIn('user_id', function ($query) use($where){
                return $query->select('id')->from('user')->where('types',$where['types']);
            });
        })->when(isset($where['duidou']) && $where['duidou'] != '', function ($query) use($where){
            return $query->whereNotIn('user_id', function ($query) use($where){
                return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
            });
        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        });
        return $query;

    }


}