<?php


namespace App\Common\Service\Rabc;


use Upp\Basic\BaseService;
use App\Common\Logic\Rabc\AdminLogsLogic;
use Hyperf\DbConnection\Db;


class AdminLogsService extends BaseService
{
    /**
     * @var AdminLogsLogic
     */
    public function __construct(AdminLogsLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with('user:id,manage_name')->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function clear(){

        $result = Db::table('admin_log')->where('id','>',0)->delete();

        return $result;
    }



}