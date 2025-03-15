<?php


namespace App\Common\Logic\Users;


use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserLocked;

class UserLockedLogic extends BaseLogic
{
    /**
     * @var UserLocked
     */
    protected function getModel(): string
    {
        return UserLocked::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){


        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){
            return $query->join('user', function ($join) use($where){
                $join->on('user_locked.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_locked.*', 'user.id as uid', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['lock_type']) && $where['lock_type'] !== '', function ($query) use($where){

            return $query->where('lock_type', $where['lock_type'] );

        })->when(isset($where['direct']) && $where['direct'] > 0, function ($query)use($where) {
            if($where['direct'] == 2){
                return $query->where('num','<',0 );
            }else{
                return $query->where('num','>',0 );
            }
        });

        return $query->orderBy('id', 'desc');

    }

}