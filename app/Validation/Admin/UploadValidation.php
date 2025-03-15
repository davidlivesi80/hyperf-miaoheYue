<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class UploadValidation
{

    public static function attrs (): array
    {
        return[
            'image' => '图片',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'image' => 'file|image|mimes:jpeg,jpg,bmp,png',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'image.file' => '请上传文件',
            'image.image' => '只能上传图片',
            'image.mimes' => '只能上传图片',
        ];
    }

}
