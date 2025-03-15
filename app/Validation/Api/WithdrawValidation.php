<?php

declare(strict_types=1);

namespace App\Validation\Api;

class WithdrawValidation
{

    public static function attrs (): array
    {
        return[
            'coin' => '币种',
            'number' => '数量',
            'series' => '通道',
            'code' => '验证码',
            'address' => '地址',
            'paysword' => '密码',
            'method'=>"验证方式"
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'coin' => 'required|in:usdt',
            'number' => 'required|numeric|gt:0',
            'series' => 'required|in:3,4',
            'code' => 'required|digits:6',
            'address' => 'required',
            'paysword' => 'required',
            'method' => 'required|in:1,0',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'coin.required' => 'coin_can_not_be_empty',//币种不能为空
            'coin.in' => 'coin_parameter_error',//币种参数错误
            'number.required' => 'number_can_not_be_empty',//数量不能为空
            'number.numeric' => 'number_only_numbers',//数量只能数值
            'number.gt' => 'number_must_gt_zero',//金额必须大于0
            'series.required' => 'series_can_not_be_empty',//通道不能为空
            'series.in' => 'series_parameter_error',//通道参数错误
            'code.required' => 'code_can_not_be_empty',//验证码不能为空
            'code.digits' => 'code_only_6_digits',//验证码只能4位数字
            'address.required' => 'address_can_not_be_empty',//提现地址不能为空
            'paysword.required' => 'paysword_can_not_be_empty',//支付密码不能为空
            'method.required' => 'method_can_not_be_empty',//验证方式不能为空
            'method.in' => 'method_parameter_error',//验证方式参数错误
        ];
    }

}
