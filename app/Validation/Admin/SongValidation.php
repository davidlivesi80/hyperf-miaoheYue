<?php

declare(strict_types=1);

namespace App\Validation\Admin;

class SongValidation
{
    public static function attrs (): array
    {
        return[
            'id' => 'tokenId',
            'owner' => '归属',
            'username' => '地址',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'id' => 'required',
            'owner' => 'required',
            'username' => 'required',
            
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'id.required' => ':attribute不能为空',
            'owner.required' => ':attribute不能为空',
            'username.required' => ':attribute不能为空',
        ];
    }

}
