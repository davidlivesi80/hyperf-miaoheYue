<?php

declare(strict_types=1);

namespace App\Validation\Api;

class TransterValidation
{

    public static function attrs (): array
    {
        return[
            'number' => '数量',
            'code'=> '验证码',
            'method'=> '类型',
            'game_id'=> '游戏',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'number' => 'required|integer|gt:0',
            'code' => 'required',
            'method'=> 'required|integer|in:1,2',
            'game_id'=> 'required',
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
            'number.gt' => ':attribute必须大于0',
            'code.required' => ':attribute不能为空',
            'method.required' => ':attribute不能为空',
            'method.integer' => ':attribute只能整数',
            'method.in'=> 'attribute参数错误',
            'game_id.required' => ':attribute不能为空',
        ];
    }

}
