<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class CardAttrValidation
{
    public static function attrs (): array
    {
        return[
            'attr_name' => '属性名称',
            'attr_type' => '分配模式',
            'attr_value' => '属性列值',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'attr_name' => 'required',
            'attr_type' => 'required',
            'attr_value' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'attr_name.required' => ':attribute不能为空',
            'attr_type.required' => ':attribute不能为空',
            'attr_value.required' => ':attribute不能为空',
        ];
    }

}
