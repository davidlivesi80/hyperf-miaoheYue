<?php

declare (strict_types=1);

namespace App\Common\Model\Rabc;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;

class AdminUser extends BaseModel
{
    use SoftDeletes;
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
        return 'admin_user';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'manage_name','password','password_salt','google2fa_secret'
        ];
    }

    /**
     * 多对多建立关系
     */
    public function roles()
    {
        return $this->belongsToMany(AdminGroup::class,'admin_user_group','user_id','group_id');
    }
    
 
    



}