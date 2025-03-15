<?php
declare (strict_types=1);

namespace App\Common\Model\Users;

use App\Common\Model\System\SysCards;
use App\Common\Model\System\SysRobot;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserRadot extends BaseModel
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
        return 'user_radot';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','robot_id','order_sn','symbol','rate','lever','total','total_num','total_oso','buy_time','pay_time','pay_type','pay_series','start_time','end_time','is_auto','aotu_num','income_time','last_time','status'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function robot()
    {
        return $this->hasOne(SysRobot::class,'id','robot_id');
    }



}
