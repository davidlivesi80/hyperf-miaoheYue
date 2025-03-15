<?php


namespace App\Common\Logic\Otc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Otc\OtcOrder;

class OtcOrderLogic extends BaseLogic
{
    /**
     * @var OtcOrder
     */
    protected function getModel(): string
    {
        return OtcOrder::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('otc_order users_uid', '=', 'user.id')->where('user.username', $where['username']);
            });

        })->when(isset($where['seller']) && $where['seller'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('otc_order seller_uid', '=', 'user.id')->where('user.username', $where['username']);
            });

        })->when(isset($where['buyer']) && $where['buyer'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('otc_order buyer_uid', '=', 'user.id')->where('user.username', $where['username']);
            });

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('users_uid', $where['user_id'])->orWhere('other_uid',$where['user_id']);

        })->when(isset($where['buyer_uid']) && $where['buyer_uid'] !== '', function ($query) use($where){

            return $query->where('buyer_uid', $where['buyer_uid'] );

        })->when(isset($where['seller_uid']) && $where['seller_uid'] !== '', function ($query) use($where){

            return $query->where('seller_uid', $where['seller_uid'] );

        })->when(isset($where['market_id']) && $where['market_id'] !== '', function ($query) use($where){

            return $query->where('market_id', $where['market_id'] );

        })->when(isset($where['otc_coin_id']) && $where['otc_coin_id'] !== '', function ($query) use($where){

            return $query->where('otc_coin_id', $where['otc_coin_id']);

        })->when(isset($where['side']) && $where['side'] !== '', function ($query) use($where){

            return $query->where('side', $where['side'] );

        })->orderBy('id', 'desc');

        return $query;
    }
}