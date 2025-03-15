<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class DeliverValidation
{
    public static function attrs (): array
    {
        return[
            'deliver_name' => '物流名称',
            'deliver_code' => '物流单号'
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'deliver_name' => 'required',
            'deliver_code' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'deliver_name.required' => ':attribute不能为空',
            'deliver_code.required' => ':attribute不能为空',
        ];
    }

}
