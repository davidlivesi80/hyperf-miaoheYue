<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class KlineValidation
{
    public static function attrs (): array
    {
        return[
            'second_id' => '交易',
            'direct' => '方向',
            'frequency'   => '频率',
            'period'=> '周期',
            'trade_time'=> '开控时间',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'second_id' => 'required',
            'direct' => 'required|integer',
            'frequency' => 'required|integer',
            'period' => 'required',
            'trade_time' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'second_id.required' => ':attribute不能为空',
            'direct.required' => ':attribute不能为空',
            'direct.integer' => ':attribute只能是整数',
            'frequency.required' => ':attribute不能为空',
            'frequency.integer' => ':attribute只能是整数',
            'period.required' => ':attribute不能为空',
            'trade_time.required' => ':attribute不能为空',
        ];
    }

}
