<?php


namespace App\Common\Logic\Otc;

use Upp\Basic\BaseLogic;
use App\Common\Model\Otc\OtcMarket;

class OtcMarketLogic extends BaseLogic
{
    /**
     * @var OtcMarket
     */
    protected function getModel(): string
    {
        return OtcMarket::class;
    }

    /**
     * 搜索器
     * @param array $where
     */
    public function search(array $where)
    {

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('otc_market user_id', '=', 'user.id')->where('user.username', $where['username']);
            });

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){//查自己

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['other_id']) && $where['other_id'] !== '', function ($query) use($where){//排除自己

            return $query->where('user_id', '<>', $where['other_id'] );

        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use($where){

            return $query->whereIn('status', $where['status'] );

        })->when(isset($where['side']) && $where['side'] !== '', function ($query) use($where){

            return $query->where('side', $where['side'] );

        })->when(isset($where['finished']) && $where['finished'] !== '', function ($query) use($where){//是否完成
            if($where['finished']){
                return $query->where('finish_time', '>' ,0);
            }else{
                return $query->where('finish_time', 0 );
            }
        })->when(isset($where['otc_coin_id']) && $where['otc_coin_id'] !== '', function ($query) use($where){

            return $query->where('otc_coin_id', $where['otc_coin_id']);

        })->when(isset($where['order_sn']) && $where['order_sn'] !== '', function ($query) use($where){

            return $query->where('order_sn', $where['order_sn'] );

        })->when(isset($where['coin_name']) && $where['coin_name'] !== '', function ($query) use($where){

            return $query->where('coin_name', '%like%',$where['coin_name'] );

        })->orderBy('id', 'desc');

        return $query;
    }
}