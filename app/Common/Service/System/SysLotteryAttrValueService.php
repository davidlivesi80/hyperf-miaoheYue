<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysLotteryAttrValueLogic;

class SysLotteryAttrValueService extends BaseService
{

    /**
     * @var SysLotteryAttrValueLogic
     */
    public function __construct(SysLotteryAttrValueLogic $logic)
    {
        $this->logic = $logic;
    }

    /**
     * 查询搜索
     */
    public function search(array $where){

        $list = $this->logic->search($where)->with('attr')->get();

        return $list;
    }
    
    public function getQueryArr(array $where){
        $list = $this->logic->search($where)->with('attr')->get()->toArray();
        $_key = [];
        foreach ($list as $val){
            $_key[] = 'attr_' . $val['id'];
        }
        return $_key;
    }

    public function create($robotId , $ids=[]){
        $attresCreate = [];
        $attresDelete = [];
        $attr_ids = $this->logic->getQuery()->where(['lottery_id'=>$robotId])->pluck('attr_id')->toArray();
        for ($i=0; $i<count($ids);$i++){
            if(in_array($ids[$i],$attr_ids)){
                continue;
            }
            $data['lottery_id'] = $robotId;
            $data['attr_id'] = $ids[$i];
            $attresCreate[] = $data;
        }
        for ($i=0; $i<count($attr_ids);$i++){
            if(in_array($attr_ids[$i],$ids)){
                continue;
            }
            $attresDelete[] = $attr_ids[$i];
        }

        if(count($attresDelete) > 0){
            $this->logic->getQuery()->whereIn('attr_id',$attresDelete)->where('lottery_id',$robotId)->delete();
        }
        if(count($attresCreate) > 0){
            $this->logic->insertAll($attresCreate);
        }
    }

    public function update($ids=[], $val=[]){
        $attresUpdate = [];
        for ($i=0; $i<count($val);$i++){
            if($val[$i]){
                $data['value'] = $val[$i];
                $this->logic->getQuery()->where('id',$ids[$i])->update($data);
            }
        }
    }




}