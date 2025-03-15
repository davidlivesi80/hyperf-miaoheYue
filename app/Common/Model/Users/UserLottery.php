<?php
declare (strict_types=1);

namespace App\Common\Model\Users;


use App\Common\Model\System\SysLottery;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserLottery extends BaseModel
{
    use HelpTrait;

    /**
     * 关闭时间错
     */
    public $timestamps = false;


    /**
     * @return string
     */
    public static function tablePk(): string
    {
        return 'id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'user_lottery';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_sn','lottery_id','lottery_type','num','bei','time','price','wei_bits','bits','ten_bits','rate','should_settle_time','created_at','updated_at','date','order_type','pay_type','status'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function userExtend()
    {
        return $this->hasOne(UserExtend::class,'user_id','user_id');
    }

    public function lottery()
    {
        return $this->hasOne(SysLottery::class,'id','lottery_id');
    }

    public function balanceLog()
    {
        return $this->hasOne(UserBalanceLog::class,'source_id','order_sn');
    }




}
