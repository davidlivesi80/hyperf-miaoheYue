<?php

declare(strict_types=1);

namespace App\Validation\Api;

class TokenValidation
{

    public static function attrs (): array
    {
        return[
            'appKey' => '密钥',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'appKey' => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'appKey.required' => 'appKey_can_not_be_empt',//密钥不能为空
        ];
    }

}
