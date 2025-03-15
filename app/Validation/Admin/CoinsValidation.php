<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class CoinsValidation
{

    public static function attrs (): array
    {
        return[
            'netIds'=> '网络ID',
            'coin_type' => '币种类型',
            'coin_name' => '币种名称',
            'coin_symbol' => '币种符合',
            'usd' => 'USD兑换比',
            'sort' => '排序',
            'image' => '图标',
            'coin_address' => '合约地址',
            'coin_abi' => '合约ABI',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'netIds' => 'required',
            'coin_type' => 'required',
            'coin_name' => 'required',
            'coin_symbol' => 'required|alpha_dash',
            'usd' =>  'required|numeric|gte:0',
            'sort' =>  'required|integer',
            'image' => 'required|url',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'netIds.required' => ':attribute不能为空',
            'coin_type.required' => ':attribute不能为空',
            'coin_name.required' => ':attribute不能为空',
            'coin_symbol.required' => ':attribute不能为空',
            'coin_symbol.alpha' => ':attribute格式错误',
            'usd.required' => ':attribute不能为空',
            'usd.numeric' => ':attribute格式错误',
            'usd.gte' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
        ];
    }

}
