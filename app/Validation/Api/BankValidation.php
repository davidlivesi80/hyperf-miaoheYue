<?php

declare(strict_types=1);

namespace App\Validation\Api;

class BankValidation
{

    public static function attrs (): array
    {
        return[
            'series' => '通道',
            'address'=>'地址',
            'real'=>'名字',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'series' => 'required|in:3,4',
            'address' => 'required',
            'real' => 'required',
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
            'address.required' => 'address_can_not_be_empty',//不能为空
            'real.required' => 'real_can_not_be_empty',//不能为空
        ];
    }

}
