<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class UserUpdateValidation
{

    public static function attrs (): array
    {
        return[
            'account' => '绑定信息',
            'password' => '用户密码',
            'paysword' => '资金密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'account' => 'required',
            'password' => 'filled|different:account|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
            'paysword' => 'filled|different:account|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'account.required' => ':attribute不能为空',
            'password.filled' => ':attribute不能为空',
            'password.different' => ':attribute不能与账号相同',
            'password.regex' => ':attribute必须是字母或数字组合，至少6位',
            'paysword.filled' => ':attribute不能为空',
            'paysword.different' => ':attribute不能与账号相同',
            'paysword.regex' => '密码格式错误,必须是字母或数字组合，至少6位',
        ];
    }

}
