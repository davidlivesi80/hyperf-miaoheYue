<?php


namespace App\Common\Service\System;

use App\Common\Logic\System\SysMarketsLogic;
use Upp\Basic\BaseService;
use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;

class SysMarketsService extends BaseService
{
    /**
     * @var SysMarketsLogic
     */
    public function __construct(SysMarketsLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;
    }

    /**
     * 查询搜索
     */
    public function columns(array $where,$field=[]){

        $list = $this->logic->search($where)->select($field)->get();

        return $list;
    }


}