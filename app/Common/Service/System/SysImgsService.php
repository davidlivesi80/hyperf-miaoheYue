<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysImgsLogic;

class SysImgsService extends BaseService
{

    /**
     * @var SysImgsLogic
     */
    public function __construct(SysImgsLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllType()
    {

        return [

            ['id'=>1,'title'=>'首页轮播,大小750px*330px'],

            ['id'=>2,'title'=>'用户轮播,大小690px*276px'],

            ['id'=>3,'title'=>'其他轮播,大小750px*330px'],

        ];

    }

    public function getAllMethod()
    {

        return [

            ['id'=>1,'title'=>'无需跳转'],

            ['id'=>2,'title'=>'外部跳转'],

            ['id'=>3,'title'=>'内部跳转']

        ];

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
    public function searchApi(array $where){

        $list = $this->logic->search($where)->get()->toArray();

        return $list;
    }

}