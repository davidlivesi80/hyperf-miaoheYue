<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysVersionLogic;

class SysVersionService extends BaseService
{

    /**
     * @var SysVersionLogic
     */
    public function __construct(SysVersionLogic $logic)
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


}