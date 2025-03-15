<?php


declare(strict_types=1);

namespace App\Validation\Admin;

class UserWalletValidation
{

    public static function attrs(): array
    {
        return [
            'password' => '密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'password' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'password.required' => ':attribute不能为空',
            'password.regex' => ':attribute必须是字母或数字组合，至少6位',
        ];
    }

}
