<?php
namespace Upp\Basic;

abstract class BaseLogic
{
    /**
     * @return BaseModel
     */
    abstract protected function getModel(): string;


    /**获取主键
     * @return string
     */
    public function getPk()
    {
        return ($this->getModel())::tablePk();
    }

    /**主键查询模型是否存在
     * @param int $id
     */
    public function exists(int $id)
    {
        return $this->fieldExists($this->getPk(), $id);
    }

    /**字段查询模型是否存在
     * @param $field
     * @param $value
     */

    public function fieldExists($field, $value, ?int $except = null): bool
    {
        $query = ($this->getModel())::query()->where($field, $value);
        if (!is_null($except)) $query->where($this->getPk(), '<>', $except);
        return $query->exists();
    }

    /**条件查询模型是否存在
     * @param $where
     * @param $value
     */
    public function whereExists(array $where): bool
    {
        return ($this->getModel())::query()->where($where)->exists();
    }

    /**创建数量
     * @param array $data
     */
    public function create(array $data)
    {
        return ($this->getModel())::create($data);
    }

    /**更新数量
     * @param int $id
     * @param array $data
     */
    public function update(int $id, array $data)
    {
        $res =  ($this->getModel())::query()->where($this->getPk(), $id)->update($data);

        if($res === false){
            return false;
        }

        return true;
    }

    /**批量更新
     * @param array $ids
     * @param array $data
     */
    public function updates(array $ids, array $data)
    {
        return ($this->getModel())::query()->whereIn($this->getPk(), $ids)->update($data);
    }

    /**更新字段
     * @param int $id
     * @param array $data
     */
    public function updateField(int $id, $field, $value)
    {
        $data[$field] = $value;

        return $this->update($id,$data);
    }

    /**批量插入或单条插入
     * @param array $data
     */
    public function insertAll(array $data)
    {
        return ($this->getModel())::query()->insert($data);
    }

    /**主键删除
     * @param int $id
     */
    public function remove(int $id)
    {
        return ($this->getModel())::destroy($id);
    }

    /**批量删除
     * @param int $id
     */
    public function batch(array $ids)
    {
        return ($this->getModel())::destroy($ids);
    }

    /**根据主键获取单个数据
     * @param int $id
     * @param array $column
     */
    public function find(int $id,array $column = ['*'])
    {
        return $this->findWhere($this->getPk(), $id,$column);
    }

    /**根据字段获取单个数据
     * @param array $field
     * @param string $value
     * @param array $column
     */
    public function findWhere($field, $value,array $column = ['*'])
    {
        return ($this->getModel())::query()->where($field,$value)->select($column)->first();
    }


    /**根据主键获取单个数据及关联数据
     * @param int $id
     * @param array $with
     * @param array $column
     */
    public function findWith(int $id, array $with = [],array $column = ['*'])
    {
        return ($this->getModel())::query()->where($this->getPk(), $id)->with($with)->select($column)->first();
    }

    /**
     * 根据条件查询如果不存在就创建
     * @param array $where
     * @return array|Model|null
     */
    public function findOrCreate(array $where,array $data)
    {
        return ($this->getModel())::firstOrCreate($where,$data);;
    }

    /**根据字段获取集合数据
     * @param array $where
     * @param string $column
     */
    public function selectWhere(array $where, array $column = ['*'])
    {
        return ($this->getModel())::query()->where($where)->select($column)->get();
    }

    /**获取查询器
     * @param $where
     */
    public function getQuery()
    {
        return ($this->getModel())::query();
    }

    /**条件查询字段值
     * @param string $where
     * @param string $value
     * @param string $field
     */
    public function getField(string $where,string $value,string $field)
    {
        return ($this->getModel()::query())->where($where,$value)->value($field);
    }
    
    /**主键查询字段值
     * @param int $value
     * @param string $field
     */
    public function idsField(int $id,string $field )
    {
        return $this->getField('id',$id,$field);
    }

    /**自增
     * @param array $id
     * @param string $field
     * @param int $num
     */
    public function incField(int $id, string $field , $num = 1)
    {
        return ($this->getModel()::query())->where($this->getPk(),$id)->increment($field,$num);
    }

    /**自减
     * @param array $id
     * @param string $field
     * @param int $num
     */
    public function decField(int $id, string $field , $num = 1)
    {
        return ($this->getModel()::query())->where($this->getPk(),$id)->decrement($field,$num);
    }
}