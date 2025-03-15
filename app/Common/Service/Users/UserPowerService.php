<?php


namespace App\Common\Service\Users;

use App\Common\Service\System\SysConfigService;
use Upp\Basic\BaseService;
use App\Common\Logic\Users\UserPowerLogic;
use Hyperf\DbConnection\Db;

class UserPowerService extends BaseService
{
    /**
     * @var UserPowerLogic
     */
    public function __construct(UserPowerLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllType()
    {
        return [
            ['id'=>0,'title'=>'系统导入'],

            ['id'=>1,'title'=>'系统操作'],

            ['id'=>2,'title'=>'双币挖矿释放'],

            ['id'=>3,'title'=>'双币挖矿释放-分享'],

            ['id'=>4,'title'=>'双币挖矿释放-团队'],

            ['id'=>5,'title'=>'流动性释放'],

            ['id'=>6,'title'=>'流动性矿释放-分享'],

            ['id'=>7,'title'=>'流动性矿释放-团队'],

            ['id'=>8,'title'=>'复利池加入'],

            ['id'=>9,'title'=>'复利池释放'],

            ['id'=>10,'title'=>'复利池提取'],

            ['id'=>11,'title'=>'双币挖矿释放-平级'],

            ['id'=>12,'title'=>'流动性矿释放-平级'],

            ['id'=>13,'title'=>'双币挖矿预约信誉度-扣除'],

            ['id'=>14,'title'=>'双币挖矿预约信誉度-奖励'],

        ];
    }

    public function getType($type)
    {
        switch ($type) {
            case 0:
                return '系统导入';
            case 1:
                return '系统操作';
            case 2:
                return '双币挖矿释放';
            case 3:
                return '双币挖矿释放-分享';
            case 4:
                return '双币挖矿释放-团队';
            case 5:
                return '流动性释放';
            case 6:
                return '流动性矿释放-分享';
            case 7:
                return '流动性矿释放-团队';
            case 8:
                return '复利池加入';
            case 9:
                return '复利池释放';
            case 10:
                return '复利池提取';
            case 11:
                return '双币挖矿释放-平级';
            case 12:
                return '流动性矿释放-平级';
            case 13:
                return '双币挖矿额度-扣除';
            case 14:
                return '双币挖矿额度-返还';
        }
    }


    /**
     * 查询构造
     */
    public function findByUid($userId)
    {

        return $this->logic->findWhere('user_id',$userId);

    }

    /**
     * 查询构造
     */
    public function search(array $where, $page=1,$perPage = 10){

        $list = $this->logic->search($where)->with(['user:id,username'])->paginate($perPage,['*'],'page',$page);

        return $list;
    }


    /**
     * 查询日志
     */
    public function logs(array $where, $page=1,$perPage = 10){

        $list = $this->app(UserPowerLogService::class)->search($where,$page,$perPage);

        return $list;
    }

    /**
     * 更新算力
     */
    public function rechargeTo($userId,$coin,$oldNums,$number,$type,$remark='',$sourceId=0,$targetId=0){

        Db::beginTransaction();
        try {
            $newNums = bcadd($oldNums,$number,6);
            $resuls =  $this->logic->getQuery()->where('user_id',$userId)->where(strtolower($coin), $oldNums)->update([strtolower($coin)=>$newNums]);
            if (!$resuls) throw new \Exception( "更新失败");
            $res = $this->app(UserPowerLogService::class)->create([
                'user_id'=>$userId,
                'target_id'=>$targetId,
                'source_id'=>$sourceId,
                'coin'=>strtolower($coin),
                'old'=>$oldNums,
                'num'=>$number,
                'new'=>$newNums,
                'type'=>$type,
                'remark'=>$remark ? $remark : $this->getType($type)
            ]);
            if (!$res) throw new \Exception( "更新失败");
            Db::commit();
            return true;

        } catch(\Throwable $e){

            Db::rollBack();
            //写入错误日志
            $error= ['file'=>$e->getFile(),'line'=>$e->getLine(),'msgs'=>$e->getMessage()];
            $this->logger('[算力更新异常]','error')->info(json_encode($error,JSON_UNESCAPED_UNICODE));
            return false;

        }
    }



}