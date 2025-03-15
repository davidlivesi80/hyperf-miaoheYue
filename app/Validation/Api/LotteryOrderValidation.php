<?php

declare(strict_types=1);

namespace App\Validation\Api;

class LotteryOrderValidation
{
    public static function attrs (): array
    {
        return[
            'lottery_id'=>'竞猜',
            'lottery_num'=>'下单金额',
            'lottery_wei'=>'位置',
            'lottery_bit'=>'位值',
            'lottery_type'=>'类型',
            //'paysword' => '支付密码',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'lottery_id' => 'required|integer',
            'lottery_num' => 'required|numeric|gt:0',
            'lottery_wei' => 'required|integer|in:1,2,3',//1个位  2十位 3个+ 十 位
            'lottery_bit' => 'required',
            'lottery_type'=> 'required|integer|in:1,2,3',//1大小  2单双 3数值
            //'paysword' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'lottery_id.required' => 'lottery_can_not_be_empty',//竞猜不能为空
            'lottery_id.integer' => 'lottery_only_numbers',//竞猜参数错误
            'lottery_num.required' => 'lottery_num_can_not_be_empty',//不能为空
            'lottery_num.numeric' => 'lottery_num_only_numbers',//金额参数错误
            'lottery_num.gt' => 'lottery_num_must_gt_zero',//金额必须大于0
            'lottery_wei.required' => 'lottery_wei_can_not_be_empty',//选位不能为空
            'lottery_wei.integer' => 'lottery_wei_only_numbers',//选位参数错误
            'lottery_wei.in' => 'lottery_wei_parameter_error',//选位参数错误
            'lottery_bit.required' => 'lottery_wei_can_not_be_empty',//位值不能为空
            'lottery_type.required' => 'lottery_type_can_not_be_empty',//不能为空
            'lottery_type.integer' => 'lottery_type_only_numbers',//类型参数错误
            'lottery_type.in' => 'lottery_type_parameter_error',//类型参数错误
            //'paysword.required' => 'paysword_can_not_be_empty',//密码不能为空

        ];
    }

}
