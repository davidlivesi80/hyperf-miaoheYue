<?php


namespace App\Validation\Api;


class WalletFoundValidation
{
    
    public static function attrs (): array
    {
        return[
            'recharge_id' =>'充值ID',
            'user_id' =>'用户ID',
            'symbol' =>'币种',
            'amount' =>'金额',
            'series_id' =>'通道ID',
            'status' =>'状态',
            'tx_id' =>'交易hash',
            'from' =>'FROM',
            'to' =>'TO',
            'create_time' =>'时间',
        ];
    }

    /**
     * 获取应用到请求的验证规则
     */
    public static function rules(): array
    {
        return [
            'recharge_id' => 'required|unique:user_recharge|gt:0',
            'user_id' => 'required|gt:0',
            'symbol' => 'required',
            'amount' => 'required',
            'series_id' => 'required',
            'status' => 'required',
            'tx_id' => 'required',
            'from' => 'required',
            'to' => 'required',
            'create_time' => 'required',
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     */
    public static function messages(): array
    {
        return [
            'recharge_id.required' => ':attribute不能为空',
            'recharge_id.unique' => ':attribute已存在',
            'recharge_id.gt' => ':attribute不能为0',
            'user_id.required' => ':attribute不能为空',
            'user_id.gt' => ':attribute不能为0',
            'symbol.required' => ':attribute不能为空',
            'amount.required' => ':attribute不能为空',
            'series_id.required' => ':attribute不能为空',
            'status.required' => ':attribute不能为空',
            'tx_id.required' => ':attribute不能为空',
            'from.required' => ':attribute不能为空',
            'to.required' => ':attribute不能为空',
            'create_time.required' => ':attribute不能为空',
        ];
    }

}