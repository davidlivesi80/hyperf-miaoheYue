<?php

declare(strict_types=1);

namespace App\Validation\Api;

class OtcPubValidation
{

    public static function attrs (): array
    {
        return[
            'coin_id' => '交易币',
            'side' => '买卖参数',
            'number' => '数量',
            'price' => '价格',
            'code' => '验证码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'coin_id' => 'required|integer',
            'side'   => 'required|integer|in:1,2',
            'number' => 'required|numeric|gt:0',
            'price'  => 'required|numeric|gt:0',
            'code' => 'required'
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'coin_id.required' => ':attribute不能为空',
            'coin_id.integer' => ':attribute格式错误',
            'side.required' => ':attribute不能为空',
            'side.integer' => ':attribute格式错误',
            'side.in' => ':attribute类型错误',
            'number.required' => ':attribute不能为空',
            'number.numeric' => ':attribute格式错误',
            'number.gt' => ':attribute必须大于0',
            'price.required' => ':attribute不能为空',
            'price.numeric' => ':attribute格式错误',
            'price.gt' => ':attribute必须大于0',
            'code.required' => ':attribute不能为空',
        ];
    }

}
