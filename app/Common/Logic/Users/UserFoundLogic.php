<?php


namespace App\Common\Logic\Users;


use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserFound;

class UserFoundLogic extends BaseLogic
{
    /**
     * @var UserFound
     */
    protected function getModel(): string
    {
        return UserFound::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){


        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){
            return $query->join('user', function ($join) use($where){
                $join->on('user_found.found_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_found.*', 'user.id as uid', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['found_id']) && $where['found_id'] !== '', function ($query) use($where){

            return $query->where('found_id', $where['found_id'] );

        });


        return $query->orderBy('id', 'desc');

    }

}