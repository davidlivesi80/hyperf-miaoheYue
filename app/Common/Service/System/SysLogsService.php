<?php


namespace App\Common\Service\System;


use Upp\Basic\BaseService;
use App\Common\Logic\System\SysLogsLogic;
use Hyperf\DbConnection\Db;


class SysLogsService extends BaseService
{
    /**
     * @var SysLogsLogic
     */
    public function __construct(SysLogsLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with('user:id,username')->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function clear(){

        $result = Db::table('sys_logs')->where('id','>',0)->delete();

        return $result;
    }



}