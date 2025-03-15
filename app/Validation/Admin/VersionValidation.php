<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class VersionValidation
{
    public static function attrs (): array
    {
        return[
            'ver_num' => '版本号',
            'package_url' => '下载链接',
            'description' => '简介',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'ver_num' => 'required',
            'package_url' => 'required',
            'description' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'ver_num.required' => ':attribute不能为空',
            'package_url.required' => ':attribute不能为空',
            'description.required' => ':attribute不能为空',
        ];
    }

}
