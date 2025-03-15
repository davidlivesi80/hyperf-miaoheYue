<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserCount;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;

class UserCountLogic extends BaseLogic
{
    /**
     * @var UserCount
     */
    protected function getModel(): string
    {
        return UserCount::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_count.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_count.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        });

        return $query->orderBy('user_id', 'desc');

    }
    
    
    /**
     * @Cacheable(prefix="sys-user-count", ttl=9000, listener="sys-user-count-update")
     */
    public function cacheableCount()
    {

        $lists =  $this->getQuery()->pluck('self','user_id')->toArray();
        

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-user-count", ttl=9000)
     */
    public function cachePutCount()
    {

        $lists =   $this->getQuery()->pluck('self','user_id')->toArray();

        return $lists;

    }




}