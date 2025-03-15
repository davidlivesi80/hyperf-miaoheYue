<?php

declare(strict_types=1);

namespace App\Validation\Api;

class LoginEmailValidation
{

    public static function attrs (): array
    {
        return[
            'source'   => '来源',
            'method' => '方式',
            'email' => '邮箱',
            'password' => '密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'source'   => 'required|in:app,web',
            'method'   => 'required|in:email,mobile',
            'email' => 'required|email',
            'password' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'source.required' => 'source_can_not_be_empty',//来源不能为空
            'source.in' => 'source_parameter_error',//来源参数错误
            'method.required' => 'method_can_not_be_empty',//方式不能为空
            'method.in' => 'method_parameter_error',//方式参数错误
            'email.required' => 'email_can_not_be_empty',//邮箱不能为空
            'email.email' => 'email_wrong_format',//邮箱格式错误
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
        ];
    }

}
