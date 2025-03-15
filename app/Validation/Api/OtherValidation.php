<?php

declare(strict_types=1);

namespace App\Validation\Api;

class OtherValidation
{

    public static function attrs (): array
    {
        return[
            'source'   => '来源',
            'account' => '钱包地址',
            'parent'  => "邀请地址"
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'source'   => 'required|in:app,web',
            'account' => 'required|regex:/^(0x)?[a-fA-F0-9]+$/',
            'parent' => 'filled|regex:/^(0x)?[a-fA-F0-9]+$/',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'source.required' => ':attribute不能为空',
            'source.in' => ':attribute类型错误',
            'account.required' => ':attribute不能为空',
            'account.regex' => ':attribute格式错误',
            'parent.filled' => ':attribute不能为空',
            'parent.regex' => ':attribute格式错误',
        ];
    }

}
