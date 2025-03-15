<?php
declare(strict_types=1);

namespace App\Validation\Api;


class RobotPaiValidation
{
    public static function attrs (): array
    {
        return[
            'series'=>'通道',
            'number'=>'金额',
            'paysword' => '支付密码',
            'paid'=> '支付方式',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'series' => 'required|in:1,3',
            'number' => 'required|numeric|gt:0',
            'paysword' => 'required',
            'paid' => 'required|integer|in:2,3,4',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'series.required' => 'series_can_not_be_empty',//通道不能为空
            'series.in' => 'series_parameter_error',//通道参数错误
            'number.required' => 'number_can_not_be_empty',//金额不能为空
            'number.numeric' => 'number_only_numbers',//金额参数错误
            'number.gt' => 'number_must_gt_zero',//金额必须大于0
            'paysword.required' => 'paysword_can_not_be_empty',//支付密码不能为空
            'paid.required' => 'paid_can_not_be_empty',//支付方式不能为空
            'paid.integer' => 'paid_only_numbers',//支付方式只能数值
            'paid.in' => 'paid_parameter_error',//支付方式参数错误
        ];
    }
}