<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserRelation;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;

class UserRelationLogic extends BaseLogic
{

    /**
     * @var UserRelation
     */
    protected function getModel(): string
    {
        return UserRelation::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_relation.uid', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_relation.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

                return $query->where('uid', $where['user_id'] );

        })->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use($where){

                return $query->where('pid', $where['pid'] );

        })->when(isset($where['pids']) && $where['pids'] !== '', function ($query) use($where){

            return $query->whereRaw('FIND_IN_SET(?,pids)',$where['pids']);

        });

        return $query;

    }


    /**
     * @Cacheable(prefix="sys-user-relation", ttl=9000, listener="sys-user-relation-update")
     */
    public function cacheableUsersRelation()
    {

        $lists =  $this->getQuery()->select('uid','pid','pids')->orderBy('uid', 'desc')->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-user-relation", ttl=9000)
     */
    public function cachePutUsersRelation()
    {

        $lists =   $this->getQuery()->select('uid','pid','pids')->orderBy('uid', 'desc')->get()->toArray();


        return $lists;

    }

}