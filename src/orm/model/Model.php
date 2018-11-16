<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-11-01 09:25:41
 * @Modified time:      2018-10-24 14:03:52
 * @Depends on Linker:  none
 * @Description:        数据模型类，提供面向对象化数据库的操作方式，用户需以继承方式实现
 */

namespace lin\orm\model;

use ArrayObject;
use Closure;
use lin\orm\model\structure\Params;
use lin\orm\model\structure\RMap;
use lin\orm\model\structure\WMap;

class Model extends ArrayObject
{
    protected $_Parent_Model_04561;
    protected $_is_multi_83018 = false;

    /**
     * 实例化时候可对属性赋值
     * @param object|array $data 对象或数组
     * @param int          $flag 存储标识
     */
    final public function __construct($data = [], int $flag = ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS)
    {
        //调用设置,只需调用一次。
        if (Params::none(static::class)) {
            $this->setting(); //置于开头防止设置里出现对数据的分支选择，产生bug
            Params::setTableAndPK(static::class);
        }
        //多记录情况，需要用另一层父模型包裹
        if ($data && is_array($data) && !key($data)) {
            $Model = static::class;
            foreach ($data as $key => $value) {
                if (!($value instanceof $Model)) {
                    $data[$key] = new $Model($value, $flag); //非当前实例则进行实例化，已实例化则直接存储
                }
                $data[$key]->setParent($this);
            }
            $this->_is_multi_83018 = true;
        }
        parent::__construct($data, $flag);
    }

    //读操作，查询数据库并实例化模型
    final public static function __callStatic($method, $args)
    {
        if (Params::none(static::class)) {
            new static; //实例化一次确保设置正常
        }
        $Map = new RMap(static::class);
        return call_user_func_array([$Map, $method], $args);
    }

    //写操作
    final public function __call($method, $args)
    {
        $Map = new WMap($this);
        return call_user_func_array([$Map, $method], $args);
    }

    //以数组形式输出数据
    final public function toArray(): array
    {
        $data = $this->getArrayCopy();
        if (!$data) {
            return [];
        }
        if (key($data)) {
            foreach ($data as $attr => $value) {
                if ($value instanceof Model) {
                    $data[$attr] = $value->toArray(); //此处递归多个可能的关联数据
                }
            }
        } else {
            foreach ($data as $index => $Model) {
                $data[$index] = $Model->toArray(); //多记录形式，其首个key一定是数字
            }
        }

        return $data;
    }
    //获得父模型
    final public function getParent():  ? object
    {
        return $this->_Parent_Model_04561;
    }
    //是否为多记录
    final public function isMulti() : bool
    {
        return $this->_is_multi_83018;
    }
    final protected function setParent($ParentModel)
    {
        $this->_Parent_Model_04561 = $ParentModel;
    }
    final protected function setTable(string $table): object
    {
        Params::setTable($table, static::class);
        return $this;
    }
    final protected function setPK(string $pk): object
    {
        Params::setPK($pk, static::class);
        return $this;
    }
    final protected function setMacro(string $macro, Closure $Closure): object
    {
        Params::setMacro($macro, $Closure, static::class);
        return $this;
    }
    final protected function setFormatter(string $formatter, Closure $Closure): object
    {
        Params::setFormatter($formatter, $Closure, static::class);
        return $this;
    }
    final protected function setRelation(string $relation, array $params = []): object
    {
        Params::setRelation($relation, $params, static::class);
        return $this;
    }

    //运行设置
    protected function setting()
    {}
}
