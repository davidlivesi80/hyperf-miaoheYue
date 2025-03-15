<?php

declare(strict_types=1);

namespace App\Validation\Api;

class SecondOrderValidation
{
    public static function attrs (): array
    {
        return[
            'market'=>'交易对',
            'direct' => '方向',
            'period'=> '周期',
            'number'=> '金额',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'market' => 'required',
            'direct' => 'required|integer|in:1,2',
            'period' => 'required|integer|gt:0',
            'number' => 'required|numeric|gt:0',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'market.required' => 'market_can_not_be_empty',//交易对不能为空
            'direct.required' => 'direct_can_not_be_empty',//方向不能为空
            'direct.integer' => 'direct_only_numbers',//方向格式错误
            'direct.in' => 'direct_parameter_error',//方向参数错误
            'period.required' => 'period_can_not_be_empty',//周期不能为空
            'period.integer' => 'period_only_numbers',//周期格式数值
            'period.gt' => 'period_parameter_error',//周期参数错误
            'number.required' => 'number_can_not_be_empty',//金额不能为空
            'number.numeric' => 'number_only_numbers',//金额格式数值
            'number.gt' => 'number_parameter_error',//金额参数错误
        ];
    }

}
