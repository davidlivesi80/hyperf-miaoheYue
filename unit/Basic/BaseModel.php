<?php

namespace Upp\Basic;

use Hyperf\DbConnection\Model\Model;

abstract class BaseModel extends Model
{

    /**
     * @return string
     */
    abstract public static function tablePk():? string;

    /**
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * @return array
     */
    abstract public static function tableAble(): array;

    /**
     * @return bool
     */
    //abstract public static function tableTime(): bool;

    /**
     * @return string
     */
    //abstract public static function tableCreatedAt(): string;

    /**
     * @return string
     */
    //abstract public static function tableUpdatedAt(): string;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->primaryKey = static::tablePk();
        $this->table = static::tableName();
        $this->fillable = static::tableAble();
        parent::__construct($data);
    }
}