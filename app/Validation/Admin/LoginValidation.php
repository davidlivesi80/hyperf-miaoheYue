<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class LoginValidation
{
    public static function attrs (): array
    {
        return[
            'username' => '账号',
            'password' => '密码',
            'secret' => '验证码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'username' => 'required',
            'password' => 'required',
            'secret'  => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'username.required' => ':attribute不能为空',
            'password.required' => ':attribute不能为空',
            'secret.required' => ':attribute不能为空',
        ];
    }

}
