<?php

declare(strict_types=1);

namespace App\Validation\Api;

class AddressValidation
{

    public static function attrs (): array
    {
        return[
            'mobile' => '联系方式',
            'consignee' => '联系人',
            'city' => '城市信息',
            'city_code' => '城市编码',
            'address' => '详细地址',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'mobile' => 'required|regex:/^1[3-8]{1}[0-9]{9}$/',
            'consignee' => 'required',
            'city' => 'required',
            'city_code' => 'filled',
            'address' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'mobile.required' => ':attribute不能为空',
            'mobile.regex' => ':attribute格式错误',
            'consignee.required' => ':attribute不能为空',
            'city.required' => ':attribute不能为空',
            'city_code.filled' => ':attribute不能为空',
            'address.required' => ':attribute不能为空',
        ];
    }

}
