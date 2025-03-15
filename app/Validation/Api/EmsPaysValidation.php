<?php

declare(strict_types=1);

namespace App\Validation\Api;

class EmsPaysValidation
{

    public static function attrs(): array
    {
        return [
            'checkKey' => '支付Key',
            'checkSign' => '支付Sign',

        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'checkKey' => 'required',
            'checkSign' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'checkKey.required' => 'checkKey_can_not_be_empty',//支付Key不能为空
            'checkSign.required' => 'checkSign_can_not_be_empty',//支付Sign不能为空
        ];
    }

}
