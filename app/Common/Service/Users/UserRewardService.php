<?php


namespace App\Common\Service\Users;

use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserRewardLogic;


class UserRewardService extends BaseService
{
    /**
     * @var UserRewardLogic
     */
    public function __construct(UserRewardLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10,$sort="id",$order="desc"){

        $sort = $sort == "acl_total" ? "reward" : $sort;
        $list = $this->logic->search($where)->select( 'id', 'user_id','income','deficit','safety','dnamic','groubs','groups',Db::raw('((income + safety) - deficit) as reward'),Db::raw('income_today - deficit_today as lirun_today'))
            ->with('user:id,username,email,mobile,is_bind')
            ->with(['counts'=>function($query){
                return $query->select('user_id','self','withdraw','recharge','recharge_sys');
            }])->orderBy($sort, $order)->paginate($perPage,['*'],'page',$page);
        $now = Carbon::now();$start = $now->startOfDay()->timestamp;$ends  = $now->endOfDay()->timestamp;
        $list->each(function ($item)use($start,$ends){
            $qudao = $this->app(UserService::class)->getQudaoByUser($item['id'],false);
            if($qudao){
                $item['account'] =  $qudao;
            }else{
                $item['account'] =   "";
            }
            $item ['second'] = $this->app(UserSecondService::class)->getQuery()->where('user_id',$item['user_id'])->sum('num');
            return $item;
        });
        return $list;

    }

    /**
     * 查询构造
     */
    public function searchRank(array $where,$limit=10,$sort="id",$order="desc",$index=0){

        $sort = $sort == "acl_total" ? "reward" : $sort;

        if($index==1){
            $list = $this->logic->search($where)->select( 'id', 'user_id','income','deficit','safety','income_week','deficit_week',Db::raw('income_week - deficit_week as reward'))
                ->with('user:id,username,email,mobile,is_bind')->orderBy($sort, $order)->limit($limit)->get()->toArray();
        }elseif ($index==2){
            $list = $this->logic->search($where)->select( 'id', 'user_id','income','deficit','safety','income_month','deficit_month',Db::raw('income_month - deficit_month as reward'))
                ->with('user:id,username,email,mobile,is_bind')->orderBy($sort, $order)->limit($limit)->get()->toArray();
        }else{
            $list = $this->logic->search($where)->select( 'id', 'user_id','income','deficit','safety','income_yestoday','deficit_yestoday',Db::raw('income_today - deficit_today as reward'))
                ->with('user:id,username,email,mobile,is_bind')->orderBy($sort, $order)->limit($limit)->get()->toArray();
        }

        foreach ($list as $key=>$value){
            if($value['user']['is_bind'] == 3){
                $list[$key]['username'] = substr_replace((string)$value['user']['mobile'],'****',2,10);
            }else{
                $list[$key]['username'] = substr_replace((string)$value['user']['email'],'****',2,10);
            }
            $list[$key]['user'] = "";
            if($index == 1){
                $list[$key]['reward'] = bcmul((string)$value['reward'],'50',6);
            }elseif($index == 2){
                $list[$key]['reward'] =bcmul((string)$value['reward'],'80',6);
            }else{
                $list[$key]['reward'] =bcmul((string)$value['reward'],'20',6);
            }

        }

        return $list;

    }

    /**
     * 查询构造
     */
    public function searchExp(array $where){

        $list = $this->logic->search($where)->select( 'id', 'user_id','income','deficit','safety','dnamic','groubs','groups',Db::raw('((income + safety) - deficit) as reward'),Db::raw('income_today - deficit_today as lirun_today'))
            ->with('user:id,username,email,mobile,is_bind')->get();
        $now = Carbon::now();$start = $now->startOfDay()->timestamp;$ends  = $now->endOfDay()->timestamp;
        return $list;

    }

    /**
     * 查询构造-赠送卷
     */
    public function searchSafety(array $where,$page=1,$perPage = 10){

        $list = $this->logic->search($where)->select( 'id', 'user_id','income','deficit','safety','safety_number')
            ->with('user:id,username,email,mobile,is_bind')->where('safety_number','>',0)->orderBy('safety_number','desc')->paginate($perPage,['*'],'page',$page);
        $lastMonthSameDay  = Carbon::now()->day(20)->format('Y-m-d 00:00:00');//上月20号
        $nowMonthSameDay  = Carbon::now()->day(21)->format('Y-m-d 00:00:00');//本月20号
        $list->each(function ($item)use($lastMonthSameDay,$nowMonthSameDay){
            list($child_yeji,$safety_real) = $this->safetyCount($item['user_id'],$lastMonthSameDay,$nowMonthSameDay);
            $item['child_yeji'] = $child_yeji;
            $item['safety_real'] = intval($safety_real);
            return $item;
        });

        return $list;

    }


    /**
     * 查询构造-赠送卷
     */
    public function safetyCount($userId,$lastMonthSameDay,$nowMonthSameDay){

        $childs = $this->app(UserRelationService::class)->getChild($userId);
        $childIds = array_column($childs,'uid');
        $safety_real = 0;$child_yeji = 0;
        if(count($childIds) > 0){//获取直推静业绩
            $rechargeMonth = $this->app(UserRechargeService::class)->getQuery()->whereIn('user_id',$childIds)->where('recharge_status', 2)->where('order_coin','usdt')
                ->where('created_at','>=' ,"2025-02-20 00:00:00")->whereIn("order_type",[3,4])->sum('order_mone');
            $withdrawMonth = $this->app(UserWithdrawService::class)->getQuery()->whereIn('user_id',$childIds)->where('withdraw_status', 2)->where('order_coin','usdt')
                ->where('created_at','>=' ,"2025-02-20 00:00:00")->whereIn("order_key",[0,1])->sum('order_mone');
            $child_yeji = bcsub((string)$rechargeMonth,(string)$withdrawMonth,6);
            if($child_yeji > 0){
                $safety_real= bcdiv((string)$child_yeji,'1500',6);
            }
        }
        return [$child_yeji,$safety_real];

    }


    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }


}