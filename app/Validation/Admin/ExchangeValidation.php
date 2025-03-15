<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class ExchangeValidation
{
     public static function attrs (): array
    {
        return[
            'give_coin' => '兑换币',
            'paid_coin' => '支付币',
            'price' => '价格',
            'rate' => '费率',
            'min_num' => '最小',
            'max_num'  => '最大',
            'sort'   => '排序',
            
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'give_coin' => 'required',
            'paid_coin' => 'required',
            'price' => 'required|numeric|gt:0',
            'rate' => 'required|numeric|gt:0',
            'min_num' => 'required|numeric|gt:0',
            'max_num' => 'required|numeric|gt:0',
            'sort' => 'required|integer',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'give_coin.required' => ':attribute不能为空',
            'paid_coin.required' => ':attribute不能为空',
            'price.required' => ':attribute不能为空',
            'price.numeric' => ':attribute只能是整数',
            'price.gt' => ':attribute必须大于0',
            'rate.required' => ':attribute不能为空',
            'rate.numeric' => ':attribute只能是整数',
            'rate.gt' => ':attribute必须大于0',
            'min_num.required' => ':attribute不能为空',
            'min_num.numeric' => ':attribute只能是整数',
            'min_num.gt' => ':attribute必须大于0',
            'max_num.required' => ':attribute不能为空',
            'max_num.numeric' => ':attribute只能是整数',
            'max_num.gt' => ':attribute必须大于0',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
        ];
    }

}
