<?php

declare (strict_types=1);

namespace App\Common\Model\Users;


use App\Common\Service\System\SysConfigService;
use Upp\Basic\BaseModel;
use Upp\Traits\HelpTrait;

class UserCount extends BaseModel
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
        return 'user_count';
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

}