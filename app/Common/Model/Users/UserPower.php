<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserPower extends BaseModel
{
    use HelpTrait;

    /**
     * 关闭时间错
     */
    public $timestamps = false;

    protected $appends  = ['total_wld','robot_income_wld_num','robot_dnamic_wld_num','robot_groups_wld_num','power_income_wld_num','total_atm','robot_income_atm_num','robot_dnamic_atm_num','robot_groups_atm_num','power_income_atm_num'];


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
        return 'user_power';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return ['user_id','edus'];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function getTotalWldAttribute()
    {
        $wld_one = bcadd((string)$this->power_income_wld,(string)$this->robot_income_wld,6);
        $wld_two = bcadd((string)$this->robot_dnamic_wld,(string)$this->robot_groups_wld,6);
        $wld_thr = bcadd((string)$wld_one,(string)$wld_two,6);
        return  bcadd((string)$this->total_earn_wld,(string)$wld_thr,6);
    }

    public function getRobotIncomeWldNumAttribute()
    {
        return  bcadd((string)$this->robot_income_wld,(string)$this->account_earn_wld,6);
    }

    public function getRobotDnamicWldNumAttribute()
    {
        return  bcadd((string)$this->robot_dnamic_wld,(string)$this->referee_earn_wld,6);
    }

    public function getRobotGroupsWldNumAttribute()
    {
        return  bcadd((string)$this->robot_groups_wld,(string)$this->team_earn_wld,6);
    }

    public function getPowerIncomeWldNumAttribute()
    {
        return  bcadd((string)$this->power_income_wld,(string)$this->speed_pool_earn_wld,6);
    }


    public function getTotalAtmAttribute()
    {
        $atm_one = bcadd((string)$this->power_income_atm,(string)$this->robot_income_atm,6);
        $atm_two = bcadd((string)$this->robot_dnamic_atm,(string)$this->robot_groups_atm,6);
        $atm_thr = bcadd((string)$atm_one,(string)$atm_two,6);
        return  bcadd((string)$this->total_earn_atm,(string)$atm_thr,6);
    }

    public function getRobotIncomeAtmNumAttribute()
    {
        return  bcadd((string)$this->robot_income_atm,(string)$this->account_earn_atm,6);
    }

    public function getRobotDnamicAtmNumAttribute()
    {
        return  bcadd((string)$this->robot_dnamic_atm,(string)$this->referee_earn_atm,6);
    }

    public function getRobotGroupsAtmNumAttribute()
    {
        return  bcadd((string)$this->robot_groups_atm,(string)$this->team_earn_atm,6);
    }

    public function getPowerIncomeAtmNumAttribute()
    {
        return  bcadd((string)$this->power_income_atm,(string)$this->speed_pool_earn_atm,6);
    }














}