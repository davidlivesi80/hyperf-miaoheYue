<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class MintValidation
{

    public static function attrs (): array
    {
        return[
            'number' => '数量'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'number' => 'required|integer|notIn:0|gt:0',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'number.required' => ':attribute不能为空',
            'number.integer' => ':attribute只能整数',
            'number.notIn' => ':attribute不能为0',
            'number.gt' => ':attribute必须大于0',
        ];
    }

}
