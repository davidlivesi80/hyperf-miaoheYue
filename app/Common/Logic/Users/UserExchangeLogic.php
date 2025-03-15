<?php


namespace App\Common\Logic\Users;

use App\Common\Model\Users\UserExchange;
use Upp\Basic\BaseLogic;

class UserExchangeLogic extends BaseLogic
{
    /**
     * @var UserExchange
     */
    protected function getModel(): string
    {
        return UserExchange::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_exchange.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_exchange.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['give']) && $where['give'] !== '', function ($query) use($where){

            return $query->where('order_give_coin', $where['give']);

        })->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use($where){

            return $query->where('order_paid_coin', $where['paid']);

        })->when(isset($where['exchange_id']) && $where['exchange_id'] !== '', function ($query) use($where){

            return $query->where('exchange_id', $where['exchange_id']);

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('created_at','>=',$where['timeStart']);

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('created_at','<',$where['timeEnd']);

        });

        return $query->orderBy('order_id', 'desc');

    }



}