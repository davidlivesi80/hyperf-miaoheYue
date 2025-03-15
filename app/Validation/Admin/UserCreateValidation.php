<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class UserCreateValidation
{

    public static function attrs (): array
    {
        return[
            'email' => '邮箱',
            'password' => '密码',
            'parent' =>'邀请人',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'email' => 'required|unique:user|email',
            'password' => 'required||different:username|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
            'parent' => 'filled|'//如果存在不能为空
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'email.required' => ':attribute不能为空',
            'email.unique' => ':attribute已被注册',
            'email.email' => ':attribute格式错误',
            'password.required' => ':attribute不能为空',
            'password.different' => 'attribute密码不能与账号相同',//
            'password.regex' => ':attribute必须是字母或数字组合，至少6位',
            'parent.filled' => ':attribute不能为空',
        ];
    }

}
