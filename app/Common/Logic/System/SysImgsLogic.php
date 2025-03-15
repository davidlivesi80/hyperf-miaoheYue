<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysImgs;

class SysImgsLogic extends BaseLogic
{
    /**
     * @var SysImgs
     */
    protected function getModel(): string
    {
        return SysImgs::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where){

            return $query->where('type', $where['type']);

        })->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('title', 'like', "%".$where['title']."%");

        })->when(isset($where['lang']) && $where['lang'] !== '', function ($query) use ($where) {

            return $query->where('lang', $where['lang']);

        })->orderBy('sort', 'desc');

        return $query;
    }


}