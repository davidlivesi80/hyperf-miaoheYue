<?php

declare(strict_types=1);

namespace App\Validation\Api;

class PayswordValidation
{

    public static function attrs (): array
    {
        return[
            'paysword' => '支付密码',
            'r_paysword' => '重复密码',
            'method'=>"验证方式"
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'paysword' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
            'r_paysword' => 'required|regex:/^[a-zA-Z][a-zA-Z0-9_]{5,15}$/',
            'method' => 'required|in:1,0',

        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'paysword.required' => 'paysword_can_not_be_empty',//支付密码不能为空
            'paysword.regex' => 'paysword_wrong_format',//密码格式错误,必须是字母或数字组合，至少6位
            'r_paysword.required' => 'r_password_can_not_be_empty',//重复密码不能为空
            'r_paysword.regex' => 'r_password_wrong_format',//重复密码格式错误
            'method.required' => 'method_can_not_be_empty',//验证方式不能为空
            'method.in' => 'method_parameter_error',//验证方式参数错误
        ];
    }

}
