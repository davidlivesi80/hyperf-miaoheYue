<?php


namespace App\Common\Service\Users;

use App\Common\Service\System\SysConfigService;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserCountLogic;
use Hyperf\DbConnection\Db;
use Upp\Traits\HelpTrait;

class UserCountService extends BaseService
{
     use HelpTrait;
    /**
     * @var UserCountLogic
     */
    public function __construct(UserCountLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with('user:id,username')->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }

    public function getSelfYeji($userId){
        //净业绩 = 充值- 提现
        $recharge_amount = $this->app(UserRechargeService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[3,4])->where('recharge_status',2)->sum("order_mone");
        $recharge_system = $this->app(UserRechargeService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[1])->where('recharge_status',2)->sum("order_mone");
        $withdraw_amount = $this->app(UserWithdrawService::class)->getQuery()->where('user_id',$userId)->whereIn('order_type',[3,4])->where('withdraw_status',2)->sum("order_mone");
        $self = bcsub((string)$recharge_amount,(string)$withdraw_amount,6);
        if(0>=$self){$self = 0;}
        $this->logic->getQuery()->where('user_id',$userId)->update(['self'=>$self,'recharge'=>$recharge_amount,'withdraw'=>$withdraw_amount,'recharge_sys'=>$recharge_system]);
        $parentIds = $this->app(UserRelationService::class)->getParent($userId);
        if(count($parentIds) > 0){
            $this->logic->getQuery()->whereIn('user_id',$parentIds)->update(['self_time'=>time()]);
        }
    }

    /**
     * 团队业绩
     */
    public function getTeamYeji($userId){
        
        $total = Db::select('SELECT SUM(self) as total FROM user_count where self > 0 AND user_id in (SELECT uid FROM user_relation where FIND_IN_SET(?,pids))',[$userId]);
        if(!isset($total[0])){
            return 0;
        }
        $total = $total[0]->total ? $total[0]->total : 0;
        return  $total;
    }

    /**
     * 团队流水
     */
    public function getTotalYeji($userId){

        $total = Db::select('SELECT SUM(money) as total FROM user_count where money > 0 AND user_id in (SELECT uid FROM user_relation where FIND_IN_SET(?,pids))',[$userId]);
        if(!isset($total[0])){
            return 0;
        }
        $total = $total[0]->total ? $total[0]->total : 0;
        return  $total;
    }

    /**
     * 团队余额
     */
    public function getTeamBalan($userId){

        $total = Db::select('SELECT SUM(self) as total FROM user_balance where usdt > 0 AND user_id in (SELECT uid FROM user_relation where FIND_IN_SET(?,pids))',[$userId]);
        if(!isset($total[0])){
            return 0;
        }
        $total = $total[0]->total ? $total[0]->total : 0;
        return  $total;
    }

    /**
     * 小区业绩
     */
    public function getMinYeji($userId){

        $child = $this->app(UserRelationService::class)->getChild($userId);
        if( 1 >= count($child)){
            return 0;
        }
        $tree = [];
        $total = 0;
        foreach ($child as $value){
            $count = $this->findByUid($value['uid']);
            $tree[] = $valueYeji =  bcadd((string)$count->self,(string)$count->team,6);
            $total = bcadd($total,$valueYeji,6);
        }
        $surplus = bcsub((string)$total, max($tree),6);
        //返回小区业绩
        return $surplus;
    }

    /**
     * 大区用户ID-根据流水计算
     */
    public function getMaxIds($child){
        if( 1 >= count($child)){
            return 0;
        }
        $tree = [];
        $childIds = [];
        foreach ($child as $value){
            $count = $this->findByUid($value['uid']);
            $yeji = bcadd((string)$count->money,(string)$count->total,6);
            $tree[] =$yeji;
            $childIds[] = $value['uid'];
        }
        $maxYeji = max($tree);
        if(0>=$maxYeji){
            return 0;
        }
        //返回大区用户ID
        $maxIds = array_search($maxYeji,$tree);
        if($maxIds === false){
            return 0;
        }
        return $childIds[$maxIds];
    }

    /*级别直推V2+V3部门*/
    public function findLevelGroup($childsIds){
        $levelVipArr = $this->app(UserRelationService::class)->getLevel($childsIds);
        return count($levelVipArr);
    }

    /*级别标准*/
    public function findLevel($userCount,$groups_rule,$groups_nums,$isText=false){
        /*执行*/
        $team = $userCount->team;
        $groups_rule_v1 = bcadd($groups_rule[0],'0',6);
        $groups_rule_v2 = bcadd($groups_rule[1],'0',6);
        $groups_rule_v3 = bcadd($groups_rule[2],'0',6);
        $groups_nums_v1 = $groups_nums[0];
        $groups_nums_v2 = $groups_nums[1];
        $groups_nums_v3 = $groups_nums[2];
        $childXiao = $this->app(UserRelationService::class)->getChild($userCount->user_id,true);
        $childsIds  = $this->app(UserRelationService::class)->getQuery()->where('pid',$userCount->user_id)->pluck('uid')->toArray();
        if(2 > count($childsIds)){
            return 0;
        }
        $levelVip2Group =  $this->findLevelGroup($childsIds);
        $level = 0;
        if( count($childXiao) >= $groups_nums_v1 && $team >= $groups_rule_v1){
            $level = 1;
        }
        if (count($childXiao) >= $groups_nums_v2 && $team >= $groups_rule_v2){
            $level = 2;
        }
        if ($levelVip2Group >= $groups_nums_v3){
            $level = 3;
        }
        if($isText){
            return ["用户ID"=>$userCount->user_id,'业绩'=>$team,"有效直推"=>$childXiao,"直推V2以上部门"=>$levelVip2Group,'目标级别'=>$level];
        }
        $this->logger('[自动升级]','other')->info(json_encode(["用户ID"=>$userCount->user_id,'业绩'=>$team,"有效直推"=>count($childXiao),"直推V2以上部门"=>$levelVip2Group,'目标级别'=>$level],JSON_UNESCAPED_UNICODE));
        return $level;

    }



}