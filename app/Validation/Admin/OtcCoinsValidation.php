<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class OtcCoinsValidation
{
    public static function attrs (): array
    {
        return[
            'coin_name' => '币种',
            'limit_max_number' => '最大发布',
            'limit_min_number' => '最小发布',
            'limit_max_price'=> '最大价格',
            'limit_min_price'=> '最小价格',
            'max_pub_num'   => '最大挂单数量',
            'rate'  => '手续'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'coin_name' => 'required',
            'limit_max_number' => 'required|numeric|gt:0',
            'limit_min_number' => 'required|numeric|gt:0',
            'limit_max_price' => 'required|numeric|gt:0',
            'limit_min_price' => 'required|numeric|gt:0',
            'max_pub_num' => 'required|integer|gte:0',
            'rate' => 'required|numeric|gte:0',
            
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'coin_name.required' => ':attribute不能为空',
            'limit_max_number.required' => ':attribute不能为空',
            'limit_max_number.numeric' => ':attribute格式错误',
            'limit_max_number.gt' => ':attribute必须大于0',
            'limit_min_number.required' => ':attribute不能为空',
            'limit_min_number.numeric' => ':attribute格式错误',
            'limit_min_number.gt' => ':attribute必须大于0',
            'limit_max_price.required' => ':attribute不能为空',
            'limit_max_price.numeric' => ':attribute格式错误',
            'limit_max_price.gt' => ':attribute必须大于0',
            'limit_min_price.required' => ':attribute不能为空',
            'limit_min_price.numeric' => ':attribute格式错误',
            'limit_min_price.gt' => ':attribute必须大于0',
            'max_pub_num.required' => ':attribute不能为空',
            'max_pub_num.integer' => ':attribute格式错误',
            'max_pub_num.gte' => ':attribute必须大于等于0',
            'rate.required' => ':attribute不能为空',
            'rate.numeric' => ':attribute格式错误',
            'rate.gte' => ':attribute必须大于等于0',
        ];
    }

}
