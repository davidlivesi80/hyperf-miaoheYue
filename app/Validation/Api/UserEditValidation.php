<?php

declare(strict_types=1);

namespace App\Validation\Api;

class UserEditValidation
{

    public static function attrs (): array
    {
        return[
            'nickname' => '昵称',
            'avatar' => '头像',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'nickname' => 'filled',
            'avatar' => 'filled'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'nickname.filled' => 'nickname_can_not_be_empty',//昵称不能为空
            'avatar.filled' => 'avatar_can_not_be_empty'//头像不能为空
        ];
    }

}
