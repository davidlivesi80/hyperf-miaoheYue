<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class UserValidation
{

    public static function attrs (): array
    {
        return[
            'username' => '账号',
            'mobile' => '手机',
            'password' => '登录密码',
            'paysword' => '支付密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'username' => 'required|unique:users|min:6',
            'mobile' => 'required|regex:/^1[3-8]{1}[0-9]{9}$/',
            'password' => 'required|min:6',
            'paysword' => 'required|min:6',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'username.required' => ':attribute不能为空',
            'username.unique' => ':attribute已被注册',
            'username.min' => ':attribute最少6位长度',
            'mobile.required' => ':attribute不能为空',
            'mobile.regex' => ':attribute格式错误',
            'password.required' => ':attribute不能为空',
            'password.min' => ':attribute最少6位长度',
            'paysword.required' => ':attribute不能为空',
            'paysword.min' => ':attribute最少6位长度'
        ];
    }

}
