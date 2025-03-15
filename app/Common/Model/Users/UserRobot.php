<?php
declare (strict_types=1);

namespace App\Common\Model\Users;

use App\Common\Model\System\SysCards;
use App\Common\Model\System\SysRobot;
use App\Common\Model\System\SysSportMatch;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserRobot extends BaseModel
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
        return 'user_robot';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','ucard_id','card_id','order_sn','symbol','rate','timer_rate','bili','timer','total','price','buy_time','pay_time','pay_type','pay_series','start_time','end_time','order_type','income_time','status'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function ucard()
    {
        return $this->hasOne(UserCards::class,'id','ucard_id');
    }

}
