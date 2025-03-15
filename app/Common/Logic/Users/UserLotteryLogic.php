<?php


namespace App\Common\Logic\Users;


use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserLottery;

class UserLotteryLogic extends BaseLogic
{
    /**
     * @var UserLottery
     */
    protected function getModel(): string
    {
        return UserLottery::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_lottery.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_lottery.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['top']) && $where['top'] !== '', function ($query) use($where){
            return $query->whereIn('user_id', function ($query) use($where){
                return $query->select('uid')->from('user_relation')->whereRaw('FIND_IN_SET(?,pids)',$where['top']);
            });
        })->when(isset($where['second_id']) && $where['second_id'] !== '', function ($query) use($where){

            return $query->where('second_id', $where['second_id'] );

        })->when(isset($where['order_sn']) && $where['order_sn'] !=='', function ($query)use($where) {

            return $query->where('order_sn',$where['order_sn'] );

        })->when(isset($where['lottery_type']) && $where['lottery_type'] !=='', function ($query)use($where) {

            return $query->where('lottery_type',$where['lottery_type'] );

        })->when(isset($where['order_type']) && $where['order_type'] !=='', function ($query)use($where) {

            return $query->where('order_type',$where['order_type'] );

        })->when(isset($where['status']) && $where['status'] !=='', function ($query)use($where) {//其他状态

            return $query->where('status',$where['status'] );

        })->when(isset($where['settle']) && $where['settle'] !=='', function ($query)use($where) {//结算状态
            if($where['settle'] == 1){
                return $query->where('settle_status',1 );
            }elseif ($where['settle'] == 2 || $where['settle'] == 0){
                return $query->where('settle_status',0 );
            }
        })->when(isset($where['types']) && $where['types'] != '', function ($query) use($where){
            return $query->whereNotIn('user_id', function ($query) use($where){
                return $query->select('id')->from('user')->where('types',$where['types']);
            });
        })->when(isset($where['duidou']) && $where['duidou'] != '', function ($query) use($where){
            return $query->whereNotIn('user_id', function ($query) use($where){
                return $query->select('user_id')->from('user_extend')->where('is_duidou',1);
            });
        })->when(isset($where['profit']) && $where['profit'] !=='', function ($query)use($where) {//盈亏状态

            return $query->whereIn('profit_status',$where['profit'] );

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('created_at','>=', $where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('created_at','<',$where['timeEnd']);

        });

        return $query;

    }

}