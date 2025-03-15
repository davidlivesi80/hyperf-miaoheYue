<?php

declare(strict_types=1);

namespace App\Validation\Api;

class CaptchaValidation
{

    public static function attrs (): array
    {
        return[
            'key' => '验证码key',
            'value' => '验证码value',
            'secret' => '验证码secret',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'key' => 'required',
            'value' => 'required',
            'secret' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'key.required' => 'key_can_not_be_empty',//验证码key不能为空
            'value.required' => 'value_can_not_be_empty',//验证码value不能为空
            'secret.required' => 'secret_can_not_be_empty',//验证码secret不能为空
        ];
    }

}
