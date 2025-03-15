<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class GoodsValidation
{

    public static function attrs (): array
    {
        return[
            'goods_name' => '币种名称',
            'goods_symbol' => '币种符合',
            'pec' => 'pec兑换比',
            'sort' => '排序',
            'image' => '图标',
            'is_buy'=> '买入开关',
            'is_sell'=> '卖出开关',
            'buy_rate' =>  '买入杠杠',
            'sell_rate' =>  '卖出手续',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'goods_name' => 'required',
            'goods_symbol' => 'required|alpha',
            'pec' =>  'required|numeric|gte:0',
            'sort' =>  'required|integer',
            'image' => 'required|url',
            'is_buy' =>  'required|integer',
            'is_sell' =>  'required|integer',
            'buy_rate' =>  'required|numeric',
            'sell_rate' =>  'required|numeric',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'goods_name.required' => ':attribute不能为空',
            'goods_symbol.required' => ':attribute不能为空',
            'goods_symbol.alpha' => ':attribute格式错误',
            'pec.required' => ':attribute不能为空',
            'pec.numeric' => ':attribute格式错误',
            'pec.gte' => ':attribute不能为空',
            'sort.required' => ':attribute不能为空',
            'sort.integer' => ':attribute只能是整数',
            'image.required' => ':attribute不能为空',
            'image.url' => ':attribute格式错误',
            'is_buy.required' => ':attribute不能为空',
            'is_buy.integer' => ':attribute只能是整数',
            'is_sell.required' => ':attribute不能为空',
            'is_sell.integer' => ':attribute只能是整数',
            'buy_rate.required' => ':attribute不能为空',
            'buy_rate.numeric' => ':attribute格式错误',
            'sell_rate.required' => ':attribute不能为空',
            'sell_rate.numeric' => ':attribute格式错误',
        ];
    }

}
