<?php

namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysFilesLogic;

class SysFilesService extends BaseService
{

    /**
     * @var SysFilesLogic
     */
    public function __construct(SysFilesLogic $logic)
    {
        $this->logic = $logic;
    }

    public function getAllCate()
    {
        return [
            ['id'=>0,'title'=>'全部图片'],

            ['id'=>1,'title'=>'其他图片'],

            ['id'=>2,'title'=>'用户上传'],
        ];
    }

    /**

     * 添加菜单

     */

    public function create($userType = 0,$userId = 0,array $data = [])
    {
        $data['user_type'] = $userType;
        $data['user_id'] = $userId;
        return $this->logic->create($data);

    }

    /**

     * 删除用户

     */
    public function batch($ids){

        return $this->logic->batch($ids);

    }

    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->paginate($perPage,['*'],'page',$page);

        return $list;
    }


}