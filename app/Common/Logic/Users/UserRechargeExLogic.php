<?php

namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserRechargeEx;

class UserRechargeExLogic extends BaseLogic
{
    /**
     * @var UserRechargeEx
     */
    protected function getModel(): string
    {
        return UserRechargeEx::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {

            return $query->where('pid', $where['pid']);

        })->orderBy('recharge_id', 'desc');

        return $query;

    }

}