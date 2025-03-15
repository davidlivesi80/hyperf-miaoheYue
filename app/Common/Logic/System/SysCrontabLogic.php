<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysCrontab;

class SysCrontabLogic extends BaseLogic
{
    /**
     * @var SysCrontab
     */
    protected function getModel(): string
    {
        return SysCrontab::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['name']) && $where['name'] !== '', function ($query) use ($where){

            return $query->where('task_name', $where['name']);

        })->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('task_title', 'like', "%".$where['title']."%");

        })->orderBy('id', 'desc');

        return $query;
    }


}