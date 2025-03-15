<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserReward extends BaseModel
{
    use HelpTrait;
    /**
     * 关闭时间错
     */
    public $timestamps = false;

    protected $appends  = ['acl_total'];
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
        return 'user_reward';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id'
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    /**业绩*/
    public function counts()
    {
        return $this->hasOne(UserCount::class,'user_id','user_id');
    }

    /**
     * @return string
     */
    public function getAclTotalAttribute()
    {
        $acl_edus =  bcsub((string) bcadd( $this->income, $this->safety,6),(string)$this->deficit,6);

        return $acl_edus;
    }


}