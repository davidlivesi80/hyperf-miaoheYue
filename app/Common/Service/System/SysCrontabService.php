<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysCrontabLogic;
use Upp\Exceptions\AppException;

class SysCrontabService extends BaseService
{
    /**
     * @var SysCrontabLogic
     */
    public function __construct(SysCrontabLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 检查key

     */
    public function checkName($key)
    {

        $res =  $this->logic->fieldExists('task_name',$key);

        if($res){
            throw new AppException('任务已存在',400);
        }

    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;
    }

}