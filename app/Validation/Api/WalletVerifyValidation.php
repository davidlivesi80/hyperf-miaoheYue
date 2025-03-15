<?php

namespace App\Validation\Api;


class WalletVerifyValidation
{

    public static function attrs(): array
    {
        return [
            'mnemonic' => '助记词',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'mnemonic' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'mnemonic.required' => 'mnemonic_can_not_be_empty',//助记词不能为空
        ];
    }

}