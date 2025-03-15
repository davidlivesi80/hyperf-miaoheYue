<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class LotteryAttrValidation
{
    public static function attrs (): array
    {
        return[
            'attr_name' => '属性名称',
            'attr_type' => '属性模式',
            'attr_unit' => '属性分组',
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
            'attr_unit' => 'required',
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
            'attr_unit.required' => ':attribute不能为空',
        ];
    }

}
