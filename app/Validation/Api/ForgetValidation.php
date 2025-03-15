<?php

declare(strict_types=1);

namespace App\Validation\Api;

class ForgetValidation
{

    public static function attrs (): array
    {
        return[
            'username' => '账号',
            'method' => '方式',
            'email' => '邮箱',
            'password' => '密码',
            'password_confirmation' => '确认密码',
            'code' =>'验证码',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'method'   => 'required|in:email,mobile',
            'email' => 'required|email',
            'password' => 'required|different:username|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/|confirmed',
            'code' => 'required|digits:4',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'username.required' => ':attribute不能为空',
            'username.regex' => ':attribute必须是字母或数字组合，至少6位',
            'email.required' => ':attribute不能为空',
            'email.email' => ':attribute格式错误',
            'password.required' => ':attribute不能为空',
            'password.different' => ':attribute不能与账号相同',
            'password.regex' => ':attribute必须是字母或数字组合，至少6位',
            'password.confirmed' => ':attribute与确认密码不一致',
            'code.required' => ':attribute不能为空',
            'code.digits' => ':attribute只能4位数字',
        ];
    }

}
