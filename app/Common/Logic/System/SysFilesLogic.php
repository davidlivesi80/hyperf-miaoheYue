<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysFiles;

class SysFilesLogic extends BaseLogic
{

    /**
     * @var SysFiles
     */
    protected function getModel(): string
    {
        return SysFiles::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['cate']) && $where['cate'], function ($query) use ($where){

            return $query->where('cate_id', $where['cate']);

        })->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('file_name', 'like', "%".$where['title']."%");

        })->orderBy('id', 'desc');

        return $query;
    }


}