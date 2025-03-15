<?php


namespace App\Common\Logic\System;

use Upp\Basic\BaseLogic;
use App\Common\Model\System\SysRobot;

class SysRobotLogic extends BaseLogic
{
    /**
     * @var BaseModel
     */
    protected function getModel(): string
    {
        return SysRobot::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where){

            return $query->where('is_show', $where['is_show']);

        })->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {

            return $query->where('title', 'like', "%".$where['title']."%");

        })->orderBy('sort', 'desc');

        return $query;
    }
}