<?php

declare(strict_types=1);

namespace App\Validation\Api;

class FeedbackValidation
{

    public static function attrs (): array
    {
        return[
            'title' => '主题',
            'content' => '内容',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'title' =>   'required|max:255|regex:/^[\x{4e00}-\x{9fa5}A-Za-z0-9\s\p{P}]+$/u',//，只允许输入中文、英文和标点符号
            'content' => 'required|max:255|regex:/^[\x{4e00}-\x{9fa5}A-Za-z0-9\s\p{P}]+$/u',//，只允许输入中文、英文和标点符号
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'title.required' => 'title_can_not_be_empty',//主题不能为空
            'title.max' => 'title_size_length',//主题最大长度255字符
            'title.regex' => 'title_wrong_format',//主题不能为空
            'content.required' => 'content_can_not_be_empty',//内容不能为空
            'content.max' => 'content_size_length',//内容最大长度255字符
            'content.regex' => 'content_wrong_format',//内容字符非法

        ];
    }

}
