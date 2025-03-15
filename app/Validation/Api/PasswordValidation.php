<?php

declare(strict_types=1);

namespace App\Validation\Api;

class PasswordValidation
{

    public static function attrs (): array
    {
        return[
            //'oldspass' => '密码',
            'password' => '密码',
            'method'=>"验证方式"
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            //'oldspass' => 'required',
            'password' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/|confirmed',
            'method' => 'required|in:1,0',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            //'oldspass.required' => 'oldspass_can_not_be_empty',//验证码不能为空
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
            'password.confirmed' => 'password_wrong_confirmed',//密码和确认密码不一致
            'method.required' => 'method_can_not_be_empty',//验证方式不能为空
            'method.in' => 'method_parameter_error',//验证方式参数错误
        ];
    }

}
