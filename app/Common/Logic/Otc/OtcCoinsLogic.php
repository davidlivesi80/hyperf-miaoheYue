<?php


namespace App\Common\Logic\Otc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Otc\OtcCoins;

class OtcCoinsLogic extends BaseLogic
{
    /**
     * @var OtcCoins
     */
    protected function getModel(): string
    {
        return OtcCoins::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['coin_id']) && $where['coin_id'] !== '', function ($query) use ($where){

            return $query->where('coin_id', $where['coin_id']);

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where){

                return $query->where('enable', $where['status']);

        })->when(isset($where['coin_name']) && $where['coin_name'] !== '', function ($query) use ($where) {

            return $query->where('title', 'like', "%".$where['coin_name']."%");

        })->orderBy('id', 'desc');

        return $query;
    }
}