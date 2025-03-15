<?php

declare(strict_types=1);

namespace App\Validation\Api;

class TransferValidation
{

    public static function attrs (): array
    {
        return[
            'coin' => '币种',
            'number' => '数量',
            'target' => '用户',
            'paysword'=> '密码',
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
            'number' => 'required|numeric',
            'target' => 'required',
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
            'number.required' => 'number_can_not_be_empty',//金额不能为空
            'number.numeric' => 'number_only_numbers',//数量只能数值
            'target.required' => 'target_can_not_be_empty',//用户不能为空
            'paysword.required' => 'paysword_can_not_be_empty',//密码不能为空
            'method.required' => 'method_can_not_be_empty',//验证方式不能为空
            'method.in' => 'method_parameter_error',//验证方式参数错误
        ];
    }

}
