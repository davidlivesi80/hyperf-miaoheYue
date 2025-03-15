<?php

declare(strict_types=1);

namespace App\Validation\Api;

class PersonalValidation
{

    public static function attrs (): array
    {
        return[
            'real_name' => '真实姓名',
            'card_id' => '身份证号',
            'card_right' => '证件正面',
            'card_left' => '证件反面',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'real_name' => 'required|alpha',
            'card_id' => 'required|regex:/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i',
            'card_right' => 'required|url',
            'card_left' => 'required|url',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'real_name.required' => ':attribute不能为空',
            'real_name.alpha' => ':attribute格式错误',
            'card_id.regex' => ':attribute不能为空',
            'card_id.required' => ':attribute不能为空',
            'card_right.required' => ':attribute不能为空',
            'card_right.url' => ':attribute格式错误',
            'card_left.required' => ':attribute不能为空',
            'card_left.url' => ':attribute格式错误',
        ];
    }

}
