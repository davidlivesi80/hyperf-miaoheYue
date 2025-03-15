<?php

namespace App\Common\Service\Users;


use Psr\SimpleCache\CacheInterface;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRelationLogic;

use Hyperf\DbConnection\Db;

class UserRelationService extends BaseService
{
    /**
     * @var UserRelationLogic
     */
    public function __construct(UserRelationLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询构造
     */
    public function search(array $where, $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->with(['user:id,username,email,mobile,is_bind','extend:user_id,level','counts:user_id,self,team,recharge,recharge_sys,withdraw','balance:user_id,usdt','reward'])->paginate($perPage, ['*'], 'page', $page);


        return $list;

    }

    /**
     * 查询构造
     */
    public function searchApi(array $where, $page = 1, $perPage = 10)
    {

        $list = $this->logic->search($where)->paginate($perPage, ['*'], 'page', $page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('uid',$userId);

    }

    /**
     * 查询上级
     */
    public function getParent($userId, $num = 0)
    {
        $pids = $this->app(CacheInterface::class)->get("user_parent_{$userId}");
        if(!$pids){
            $pids = $this->logic->getQuery()->where('uid',$userId)->value('pids');
            if(!$pids){return [];}
            //挪线要清除缓存
            $this->app(CacheInterface::class)->set("user_parent_{$userId}", $pids,86400);
        }
        $pids = explode(',',$pids);
        //截取长度
        return $num ? array_reverse(array_slice($pids,-$num)) : array_reverse($pids);
    }
    
    /**
     * 查询一代二代用户
     */
    public function getChildsLevel($userId, $num=0)
    {
        $childsIds = $this->logic->getQuery()->where('pid',$userId)->pluck('uid')->toArray();
        if($num == 1){
            $childsIds = $this->logic->getQuery()->whereIn('pid',$childsIds)->pluck('uid')->toArray();
        }
        return $childsIds;
    }
    

    /**
     * 查询几代
     */
    public function getChilds($userId, $num=0)
    {
        $pids = $this->logic->getQuery()->where('uid',$userId)->value('pids');
        $pids_arr = explode(',',$pids);
        $list = $this->logic->getQuery()->whereRaw('FIND_IN_SET(?,pids)',$userId)->get()->toArray();
        $idx = count($pids_arr) + $num;
        $childs = [];
        foreach ($list as $key => $value){
            $pids_value = explode(',',$value);
            if(count($pids_value) == $idx ){
                $childs[] = $value['uid'];
            }
        }
        return $childs;
    }

    /**
     * 查询直推
     */
    public function getChild($userId,$isACtive=false)
    {
        if ($isACtive){
            $childs = $this->logic->getQuery()->where('pid',$userId)->join('user_count', function ($join){
                $join->on('user_relation.uid', '=', 'user_count.user_id')->where(function ($query){
                    return $query->where('user_count.recharge', '>',0)->orWhere('user_count.recharge_sys', '>',0);
                })->where('user_count.last_time','>',0);
            })->pluck('uid');
        }else{
            $childs = $this->logic->getQuery()->where('pid',$userId)->get();
        }

        return $childs ? $childs->toArray() : [];
    }

    /**
     * 伞下级别
     */
    public function getLevel($childIds)
    {
        //伞下不线相同级别
        $levels = Db::table('user_extend')->whereIn('user_id',$childIds)->where('level','>=',2)->pluck('user_id')->toArray();
        return $levels;
    }

    /**
     * 查询伞下
     * $isDuidou if 排除对都
     */
    public function getTeams($userId,$isACtive=false)
    {

        if($isACtive){
            $teamIds = $this->logic->getQuery()->whereRaw('FIND_IN_SET(?,pids)',$userId)->join('user_count', function ($join){
                $join->on('user_relation.uid', '=', 'user_count.user_id')->where('user_count.last_time','>',0);
            })->pluck('uid')->toArray();
        }else{
            $teamIds = $this->logic->getQuery()->whereRaw('FIND_IN_SET(?,pids)',$userId)->pluck('uid')->toArray();
        }
        return $teamIds;
    }

    /**
     * 大区用户
     */
    public function getMaxUser($userId){

        $child = $this->app(UserRelationService::class)->getChild($userId);
        if( 1 >= count($child)){
            return [];
        }
        $tree = [];
        $uids = [];
        $surplus = 0;
        foreach ($child as $value){
            $count = $this->findByUid($value['uid']);
            $tree[] = bcadd((string)$count->self,(string)$count->team,6);
            $uids[] = $value['uid'];

        }
        if(0 >= count($tree)){
            return [];
        }
        $maxKey =  array_search(max($tree),$tree);
        //返回大区用户
        return $uids[$maxKey];
    }







}