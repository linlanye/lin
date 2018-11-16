<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-31 14:05:40
 * @Modified time:      2018-11-07 08:54:02
 * @Depends on Linker:  None
 * @Description:        模型写操作，包含关联模型
 */
namespace lin\orm\model\structure;

use lin\orm\model\structure\Map;
use lin\orm\model\structure\Params;

class WMap extends Map
{
    private $Model;
    private $isStrict          = false;
    private $inTrans           = false; //处于事务中标记
    protected static $terminal = [
        'update' => 1, 'insert' => 1, 'delete' => 1,
    ];

    final public function getModel(): object
    {
        if (is_array($this->Model)) {
            return current($this->Model);
        }
        return $this->Model;
    }

    //事务写入是否为严格模式，true则在一次模型写入中任意失败一条都为失败，false则任意一条写入成功都为成功
    final public function setStrictTrans(): object
    {
        $this->isStrict = true;
        return $this;
    }

    //初始化参数
    protected function initParams($Model)
    {
        $class = get_class($Model);
        if (!$Model->isMulti()) {
            $Model = [$Model]; //处理成多记录形式
        }
        $this->Model           = $Model;
        $this->params          = Params::get($class);
        $this->params['class'] = $class;
    }

    /**
     * 执行处理
     * @param  string $method 执行的方法，insert、update或delete
     * @param  bool   $isRoot 是否为根节点，关联查询时候用
     * @return int            影响记录数
     */
    protected function execute($method, $isRoot)
    {
        if (!$isRoot) {
            return $this->handle($method);
        }

        //根节点处理事务和备份。多记录和关联操作使用事务
        $this->backup = [$this->params, $this->Model, serialize($this->Model)]; //记录参数、根模型句柄、其原始数据
        if ((is_object($this->Model) || $this->withParams['r']) && !$this->Driver->inTransaction()) {
            $this->inTrans = true;
            $this->Driver->beginTransaction();
        }

        $this->isRoot = false;
        $r            = $this->handle($method); //可能存在递归，非跟节点

        //是否处于事务中
        if ($this->inTrans) {
            if ($r) {
                $this->Driver->commit();
            } else {
                $this->Driver->rollBack();
            }
            $this->inTrans = false;
        }
        if (!$r) {
            $this->restore($this->backup[1], unserialize($this->backup[2])); //操作失败复原数据
        }
        unset($this->backup[2]); //释放掉备份数据，避免reset里再次调用

        $this->reset(); //重置便于复用
        return $r;
    }

    //获得处理结果
    private function handle($method)
    {
        $t = microtime(true);
        //处理主模型
        $isInsert = $method == 'insert';
        $isDelete = $method == 'delete';
        $rowCount = 0;

        //执行写操作
        $this->Creator->setSnapshot(); //批量处理，记录此前操作
        foreach ($this->getData($method) as $index => $value) {
            $this->Creator->restoreSnapshot();
            if (!$isInsert) {
                foreach ($this->params['pk'] as $pk) {
                    if (!isset($value[$pk])) {
                        $this->exception('缺少主键数据，更新或删除无法执行');
                    }
                    $this->Creator->where($pk, $value[$pk]);
                    if ($isDelete) {
                        unset($this->Model[$index][$pk]); //数据对删除无效，但需删除模型主键
                    } else {
                        unset($value[$pk]); //更新无需主键数据，但不能删除主键
                    }
                }
            }
            $this->Creator->withData($value, true)->execute();
            $this->Driver->execute($this->Creator->getSQL(), $this->Creator->getParameters());
            $r = $this->Driver->rowCount();
            if (!$r) {
                if ($this->isStrict) {
                    if ($this->Debug) {
                        $this->Debug->write($this->params['class'], 0, microtime(true) - $t, $method);
                    }
                    return 0; //严格模式下任一记录操作失败皆失败
                }
                continue;
            }
            $rowCount += $r;
            //对insert补充自增id
            if ($isInsert) {
                $first_pk = current($this->params['pk']);
                if (!isset($this->Model[$index][$first_pk])) {
                    $this->Model[$index][$first_pk] = $this->Driver->lastID(); //尝试补上自增id
                }
            }
        }
        if ($this->Debug) {
            $this->Debug->write($this->params['class'], $rowCount, microtime(true) - $t, $method);
        }

        //执行关联操作
        if ($rowCount && $this->withParams['r']) {
            $relation_params = [];
            //获得关联参数
            foreach ($this->withParams['r'] as $attr) {
                $params                 = Params::getRelation($this->params['class'], $attr);
                $relation_params[$attr] = $params;
            }
            //执行关联写
            foreach ($relation_params as $attr => $params) {
                foreach ($this->Model as $index => $Master) {
                    $r = $this->handleRelation($Master, $attr, $method, $params);
                    if ($r) {
                        $rowCount += $r;
                    } else if ($this->isStrict) {
                        $rowCount = 0;
                        break 2; //严格模式下任一记录操作失败皆失败
                    }
                }
            }
        }

        return $rowCount;
    }

    //获得关联模型处理结果
    private function handleRelation($Master, $attr, $method, $params)
    {
        //去除无用数据
        if (!isset($Master[$attr]) || !($Master[$attr] instanceof $params['class'])) {
            $this->notice('当前主模型缺少有效从模型，已略过关联操作');
            return 0;
        }
        $Slave = $Master[$attr];

        //同步更新和插入的主从字段值
        if ($method != 'delete') {
            foreach ($params['mk'] as $index => $mk) {
                $sk = $params['sk'][$index];
                if (!isset($Master[$mk])) {
                    $this->notice('当前主模型缺少有效关联主字段，已略过关联操作'); //此时所有主模型已经和数据库同步完毕
                    return 0;
                }
                //同步mk和sk
                if ($Slave->isMulti()) {
                    foreach ($Slave as $_Slave) {
                        $_Slave[$sk] = $Master[$mk];
                    }
                } else {
                    $Slave[$sk] = $Master[$mk];
                }

            }
        }
        $this->initParams($Slave); //每一轮查询都需初始化该关联模型查询的参数，存在深层递归
        $this->withParams = ['f' => [], 'r' => [], 'm' => []]; //每一轮查询都需清空上一轮使用的with参数

        //存在用户自定义关联操作
        if (isset($params[$method])) {
            if (is_callable($params[$method])) {
                $mks = [];
                foreach ($params['mk'] as $mk) {
                    $mks[$mk] = $Master[$mk];
                }
                return (int) call_user_func_array($params[$method], [$this, $mks]);
            }
            return $params[$method]; //非回调则直接返回用户设定值
        }
        return $this->$method();

    }

    private function getData($method)
    {
        $notMulti = is_array($this->Model);
        if ($notMulti) {
            $data = current($this->Model)->getArrayCopy();
        } else {
            $data = $this->Model->toArray();
        }
        if (!$data) {
            $this->exception('关系模型数据为空，不可写入');
        }
        if ($notMulti) {
            $data = [$data];
        }

        if ($method == 'delete') {
            return $data; //删除数据无需后续操作
        }

        //去掉关联数据
        if ($this->withParams['r']) {
            foreach ($this->withParams['r'] as $attr) {
                foreach ($data as $index => $value) {
                    unset($data[$index][$attr]);
                }
            }
        }

        //格式化数据
        if ($this->withParams['f']) {
            foreach ($this->withParams['f'] as $name) {
                $formatter = $this->params['formatter'][$name] ?? null;
                if ($formatter) {
                    foreach ($data as $index => $value) {
                        $data[$index] = call_user_func($formatter, $value);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 操作失败后，复原原模型数据，数据顺序也必须一致
     * @param  array|object $currentModel 当前待恢复模型
     * @param  array|object $rawModel     对应的原始模型
     * @return void
     */
    private function restore($currentModel, $rawModel)
    {
        $baseModel = 'lin\\orm\\model\\Model';
        if (!is_array($currentModel) && !$currentModel->isMulti()) {
            $currentModel = [$currentModel];
            $rawModel     = [$rawModel]; //处理成多记录形式
        }
        //依次按顺序恢复
        foreach ($rawModel as $index => $Model) {
            $current = $currentModel[$index]->exchangeArray([]); //置换清空当前数据
            foreach ($Model as $attr => $value) {
                if ($value instanceof $baseModel) {
                    $this->restore($current[$attr], $value); //恢复关联模型
                }
                $currentModel[$index][$attr] = $value; //重新赋值
            }
        }
    }

    protected function reset()
    {
        if ($this->inTrans) {
            $this->inTrans = false; //恢复事务标记
            $this->Driver->rollBack();
        }
        if (isset($this->backup[2])) {
            $this->restore($this->backup[1], unserialize($this->backup[2])); //恢复原数据
        }
        $this->params = $this->backup[0]; //恢复原模型参数
        $this->Model  = $this->backup[1]; //恢复原模型句柄

        $this->backup     = null;
        $this->withParams = ['f' => [], 'r' => [], 'm' => []];
        $this->isRoot     = true;
        $this->Creator->reset();
    }
}
