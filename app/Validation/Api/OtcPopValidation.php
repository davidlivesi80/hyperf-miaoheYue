<?php

namespace App\Validation\Api;

class OtcPopValidation
{
    public static function attrs (): array
    {
        return[
            'market_id' => '交易币',
            'pay_coin' => '支付',
            'code' => '验证码'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'market_id' => 'required|integer',
            'pay_coin' => 'required|in:usdt',
            'code' => 'required'

        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'market_id.required' => ':attribute不能为空',
            'market_id.integer' => ':attribute格式错误',
            'pay_coin.required' => ':attribute不能为空',
            'pay_coin.in' => ':attribute只能USDT',
            'code.required' => ':attribute不能为空',
        ];
    }

}