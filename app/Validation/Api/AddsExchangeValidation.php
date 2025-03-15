<?php

declare(strict_types=1);

namespace App\Validation\Api;

class AddsExchangeValidation
{

    public static function attrs (): array
    {
        return[
            'exchange_id' => '交易对',
            'number' => '数量',
            'code' => '验证码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'exchange_id' => 'required|integer',
            'number' => 'required|numeric|gt',
            'code' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'exchange_id.required' => 'exchange_id_can_not_be_empty',//交易对不能为空
            'exchange_id.integer' => 'exchange_id_only_numbers',//交易对只能数值
            'number.required' => 'number_can_not_be_empty',//数量不能为空
            'number.numeric' => 'number_only_numbers',//数量只能数值
            'number.gt' => 'number_must_gt_zero',//金额必须大于0
            'code.required' => 'code_can_not_be_empty',//验证码不能为空
        ];
    }

}
