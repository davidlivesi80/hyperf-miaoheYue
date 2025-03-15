<?php


namespace App\Common\Logic\Users;

use Upp\Basic\BaseLogic;
use App\Common\Model\Users\UserBalanceLog;


class UserBalanceLogLogic extends BaseLogic
{
    /**
     * @var UserBalanceLog
     */
    protected function getModel(): string
    {
        return UserBalanceLog::class;
    }

    /**
     * 查询构造
     */
    public function search(array $where){

        $query = $this->getQuery()->when(isset($where['username']) && $where['username'] !== '', function ($query) use($where){

            return $query->join('user', function ($join) use($where){
                $join->on('user_balance_log.user_id', '=', 'user.id')->where( function ($query) use($where){
                    $query->where('user.username', 'like', '%'. trim($where['username']).'%' )->orWhere('user.email','like', '%'. trim($where['username']).'%' )->orWhere('user.mobile','like', '%'. trim($where['username']).'%' );
                });
            })->select('user_balance_log.*', 'user.id as user_id', 'user.username');

        })->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use($where){

            return $query->where('user_id', $where['user_id'] );

        })->when(isset($where['coinames']) && $where['coinames'] !=='', function ($query)use($where) {

            return $query->where('coin',strtolower($where['coinames']));

        })->when(isset($where['coiname']) && $where['coiname'] !=='', function ($query)use($where) {

            return $query->where('coin',strtolower($where['coiname']));

        })->when(isset($where['type']) && $where['type'] !=='', function ($query)use($where) {

            return $query->where('type',$where['type'] );

        })->when(isset($where['remark']) && $where['remark'] !=='', function ($query)use($where) {

            return $query->where('remark','like','%'.$where['remark'].'%' );

        })->when(isset($where['types']) && count($where['types']) > 0, function ($query)use($where) {

            return $query->whereIn('type',$where['types'] );

        })->when(isset($where['direct']) && $where['direct'] > 0, function ($query)use($where) {
            if($where['direct'] == 2){
                return $query->where('num','<',0 );
            }else{
                return $query->where('num','>',0 );
            }

        })->when(isset($where['timeStart']) && $where['timeStart'] !=='', function ($query)use($where) {

            return $query->where('user_balance_log.created_at','>=',$where['timeStart'] );

        })->when(isset($where['timeEnd']) && $where['timeEnd'] !=='', function ($query)use($where){

            return $query->where('user_balance_log.created_at','<',$where['timeEnd']);

        });

        return $query->orderBy('id', 'desc');

    }

}