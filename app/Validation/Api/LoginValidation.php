<?php

declare(strict_types=1);

namespace App\Validation\Api;

class LoginValidation
{

    public static function attrs (): array
    {
        return[
            'source'   => '来源',
            'account' => '邮箱/手机',
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
            'account' => 'required',
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
            'account.required' => 'account_can_not_be_empty',//邮箱或手机不能为空
            'password.required' => 'password_can_not_be_empty',//密码不能为空
            'password.regex' => 'password_wrong_format',//密码必须是字母或数字组合，至少6位
        ];
    }

}
