<?php
declare (strict_types=1);

namespace App\Common\Model\Users;


use App\Common\Model\System\SysSafety;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserSafety extends BaseModel
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
        return 'user_safety';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','order_sn','safety_id','price','total','period','pay_time','buy_time','order_type','status'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function safety()
    {
        return $this->hasOne(SysSafety::class,'id','safety_id');
    }


}
