<?php

declare (strict_types=1);

namespace App\Common\Model\Users;

use Hyperf\Database\Model\SoftDeletes;
use Upp\Basic\BaseModel;
use App\Common\Model\System\SysGame;

class UserGame extends BaseModel
{
    use SoftDeletes;
    /**
     * @return string
     */
    public static function tablePk(): string
    {
        return 'id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'user_game';
    }

    /**
     * @return array
     */
    public static function tableAble(): array
    {
        return [
            'user_id','order_sn','game_id','order_number','order_amount','order_content','order_coin','order_paid '];
    }

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }

    public function game()
    {
        return $this->hasOne(SysGame::class,'id','game_id');
    }


}