<?php

declare(strict_types=1);

namespace App\Validation\Api;

class BindValidation
{

    public static function attrs (): array
    {
        return[
            'code' => '验证码',
            'email' => '邮箱',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'code' => 'required|digits:6',
            'email' => 'required|unique:user|email',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'code.required' => 'code_can_not_be_empty',//验证码不能为空
            'code.digits' => 'code_only_6_digits',//验证码只能4位数字
            'email.required' => 'email_can_not_be_empty',////邮箱不能为空
            'email.unique' => 'email_alread_occupied',//邮箱已被占用
            'email.email' => 'email_wrong_format',//邮箱格式错误
        ];
    }

}
