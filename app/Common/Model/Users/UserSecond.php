<?php
declare (strict_types=1);

namespace App\Common\Model\Users;


use App\Common\Model\System\SysSecond;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserSecond extends BaseModel
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
        return 'user_second';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_sn','second_id','market','symbol','num','time','price','fee','fee_rate','scene','period','direct','should_settle_time','created_at','updated_at','date','delay','order_type','status'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function userExtend()
    {
        return $this->hasOne(UserExtend::class,'user_id','user_id');
    }

    public function second()
    {
        return $this->hasOne(SysSecond::class,'id','second_id');
    }

    public function balanceLog()
    {
        return $this->hasOne(UserBalanceLog::class,'source_id','order_sn');
    }




}
