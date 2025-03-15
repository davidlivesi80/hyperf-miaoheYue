<?php

declare(strict_types=1);

namespace App\Validation\Api;

class CodeValidation
{

    public static function attrs (): array
    {
        return[
            'code' => '验证码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'code' => 'required|digits:6',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'code.required' => 'code_can_not_be_empty',//验证码不能为空
            'code.digits' => 'code_only_6_digits',//验证码只能6位数字
        ];
    }

}
