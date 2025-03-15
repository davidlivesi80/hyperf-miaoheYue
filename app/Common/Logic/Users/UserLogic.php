<?php


namespace App\Common\Logic\Users;

use App\Common\Service\Users\UserService;
use Upp\Basic\BaseLogic;
use App\Common\Model\Users\User;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CachePut;
use Upp\Exceptions\AppException;

class UserLogic extends BaseLogic
{

    /**
     * @var User
     */
    protected function getModel(): string
    {
        return User::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->where('username','like', '%'. trim($where['username']).'%' )->orWhere('email','like', '%'. trim($where['username']).'%' )->orWhere('mobile','like', '%'. trim($where['username']).'%' );

        })->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use($where){
            return $query->where( function($query) use ($where){
                $query->where('mobile','like', '%'. trim($where['mobile']).'%' );
            });
        })->when(isset($where['email']) && $where['email'] !== '', function ($query) use($where){
            return $query->where( function($query) use ($where){
                $query->where('email','like', '%'. trim($where['email']).'%' );
            });
        })->when(isset($where['types']) && $where['types'] != '', function ($query) use($where){

            return $query->where('types', $where['types'] );

        })->when(isset($where['id']) && $where['id'] != '', function ($query) use($where){

            return $query->where('id', $where['id'] );

        })->when(isset($where['login_ip']) && $where['login_ip'] != '', function ($query) use($where){

            return $query->where('login_ip', $where['login_ip'] );

        })->when(isset($where['pid']) && $where['pid'] != '', function ($query) use($where){
            return $query->join('user_relation', function ($join) use($where){
                $join->on('user.id', '=', 'user_relation.uid')->where('user_relation.pid', $where['pid']);
            })->select('user.*', 'user_relation.uid', 'user_relation.pid');
        })->when(isset($where['top']) && $where['top'] !== '', function ($query) use($where){
            return $query->whereIn('user.id', function ($query) use($where){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$where['top']);
            });
        })->when(isset($where['level']) && $where['level'] != '', function ($query) use($where){
            return $query->join('user_extend', function ($join) use($where){
                $join->on('user.id', '=', 'user_extend.user_id')->where('user_extend.level', $where['level']);
            })->select('user.*', 'user_extend.user_id', 'user_extend.level');;
        })->when(isset($where['duidou']) && $where['duidou'] != '', function ($query) use($where){
            return $query->join('user_extend', function ($join) use($where){
                $join->on('user.id', '=', 'user_extend.user_id')->where('user_extend.is_duidou', $where['duidou']);
            })->select('user.*', 'user_extend.user_id', 'user_extend.level');;
        })->when(isset($where['recharge']) && $where['recharge'] != '', function ($query) use($where){
            return $query->whereIn('user.id', function ($query) use($where){
                return $query->select('user_id')->from('user_count')->where('user_count.recharge', '>',0);
            })->where('user.types','<>',3);
        })->when(isset($where['balance']) && $where['balance'] != '', function ($query) use($where){
            return $query->whereIn('user.id', function ($query) use($where){
                return $query->select('user_id')->from('user_count')->where('user_count.recharge', '>',0)->where('user_count.recharge', '<=',$where['balance']);
            })->where('user.types','<>',3);
        })->when(isset($where['remark']) && $where['remark'] != '', function ($query) use($where){
            if($where['remark'] == 1){
                return $query->whereNotNull('remark');
            }
        })->orderBy('id', 'desc');

        return $query;

    }

    /**
     * 查询构造
     */
    public function findByOrWhere($username){

        $query = $this->getQuery()->where('id', intval($username) )->orWhere('username',trim($username))->orWhere('email',trim($username) )->orWhere('mobile',trim($username) );

        return $query;

    }

    /**
     * @Cacheable(prefix="sys-user", ttl=9000, listener="sys-user-update")
     */
    public function cacheableUsers()
    {

        $lists =  $this->getQuery()->select('id','username')->orderBy('id', 'desc')->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-user", ttl=9000)
     */
    public function cachePutUsers()
    {

        $lists =   $this->getQuery()->select('id','email')->orderBy('id', 'desc')->get()->toArray();


        return $lists;

    }

    /**
     * @Cacheable(prefix="sys-qudao", ttl=86400, listener="sys-qudao-update")
     */
    public function cacheableQudao()
    {

        $lists =  $this->getQuery()->select('id','username','email','mobile','is_bind')->where('types',2)->orderBy('id', 'desc')->get()->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-qudao", ttl=86400)
     */
    public function cachePutQudao()
    {

        $lists =   $this->getQuery()->select('id','username','email','mobile','is_bind')->where('types',2)->orderBy('id', 'desc')->get()->toArray();


        return $lists;

    }

    /**
     * @Cacheable(prefix="sys-userWhite", ttl=86400, listener="sys-userWhite-update")
     */
    public function cacheableUserWhite()
    {

        $lists =  $this->getQuery()->where('types',3)->orderBy('id', 'desc')->pluck('id')->toArray();

        return $lists;

    }

    /**
     * @CachePut(prefix="sys-userWhite", ttl=86400)
     */
    public function cachePutUserWhite()
    {

        $lists =   $this->getQuery()->where('types',3)->orderBy('id', 'desc')->pluck('id')->toArray();


        return $lists;

    }





}