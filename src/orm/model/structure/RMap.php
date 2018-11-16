<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-03 15:41:40
 * @Modified time:      2018-11-02 14:12:43
 * @Depends on Linker:  None
 * @Description:        提供模型的查询方法，关联查询可无限递归
 */
namespace lin\orm\model\structure;

use lin\orm\model\structure\Map;
use lin\orm\model\structure\Params;

class RMap extends Map
{
    protected static $terminal = [ //终止方法
        'select' => 1, 'one' => 1,
        'max'    => 1, 'min' => 1, 'sum' => 1, 'avg' => 1, 'count' => 1,
    ];

    //使用主键快速查找单条记录
    public function find($pk, ...$pks): object
    {
        array_unshift($pks, $pk);
        if (count($pks) !== count($this->params['pk'])) {
            $this->exception('主键数量不匹配');
        }
        $condition = [];
        foreach ($this->params['pk'] as $index => $pk) {
            $condition[$pk] = $pks[$index]; //一一对应
        }
        $this->Creator->where($condition);
        return $this->one();
    }

    protected function initParams($class)
    {
        $this->params          = Params::get($class);
        $this->params['class'] = $class;
    }

    //执行查询，并存储结果
    protected function execute($method, $isRoot)
    {
        if (!$isRoot) {
            return $this->handle($method);
        }
        $this->isRoot = false;
        $this->backup = $this->params; //备份原始参数
        $r            = $this->handle($method); //可能存在递归，非跟节点
        $this->reset(); //重置便于复用

        return $r;
    }

    private function handle($method)
    {
        $t = microtime(true);
        //1.聚合查询
        if ($method != 'select' && $method != 'one') {
            $this->Creator->execute();
            $this->Driver->execute($this->Creator->getSQL(), $this->Creator->getParameters());
            $data = $this->Driver->fetchAssoc();
            if (!$data) {
                return null;
            }
            $data = end($data);
            if ($this->withParams['f']) {
                $data = $this->format($data);
            }
            if ($this->Debug) {
                $this->Debug->read($this->params['class'], 1, microtime(true) - $t);
            }
            return $data;
        }

        //2.非聚合查询
        $this->Creator->fields(implode(',', $this->params['pk'])); //补上主键
        $relation_params = []; //可能存在的关联参数

        //存在关联查询，获得参数补充查询关联字段
        if ($this->withParams['r']) {
            foreach ($this->withParams['r'] as $attr) {
                $params = Params::getRelation($this->params['class'], $attr);
                $this->Creator->fields(implode(', ', $params['mk'])); //补上关联主字段
                $relation_params[$attr] = $params;
            }
        }

        //执行
        $this->Creator->execute();
        $this->Driver->execute($this->Creator->getSQL(), $this->Creator->getParameters());
        $data = $this->Driver->fetchAllAssoc();
        if (!$data) {
            if ($this->Debug) {
                $this->Debug->read($this->params['class'], 0, microtime(true) - $t);
            }
            return null;
        }

        //格式化
        if ($this->withParams['f']) {
            if ($relation_params) {
                $raw = $data; //存在关联查询需留原始数据
            }
            foreach ($data as $key => $value) {
                $data[$key] = $this->format($value);
            }
        }
        if ($this->Debug) {
            $this->Debug->read($this->params['class'], count($data), microtime(true) - $t);
        }

        //关联操作
        $Master = $this->params['class'];
        if ($relation_params) {
            $raw = $raw ?? $data;

            //只使用未格式化的数据做关联查询
            foreach ($relation_params as $attr => $params) {
                foreach ($raw as $index => $item) {
                    $Slave = $this->handleRelation($item, $params); //存在递归
                    if (is_object($Slave) && !empty($params['merge'])) {
                        $slave_data = $Slave->toArray();
                        if ($Slave->isMulti()) {
                            $data[$index][$attr] = $slave_data; //多记录只保留为数组
                        } else {
                            $data[$index] = array_merge($data[$index], $slave_data); //单记录合并为属性
                        }
                    } else {
                        $data[$index][$attr] = $Slave;
                    }
                }
            }
        }

        if ($method == 'one') {
            $data = current($data);
        }
        return new $Master($data);
    }

    private function handleRelation($master, $params)
    {
        $this->initParams($params['class']); //每一轮查询都需初始化该关联模型查询的参数，存在深层递归
        $this->withParams = ['f' => [], 'r' => [], 'm' => []]; //每一轮查询都需清空上一轮使用的with参数

        //获得从查询条件
        $mks = [];
        $sks = [];
        foreach ($params['mk'] as $index => $mk) {
            if (!isset($master[$mk])) {
                //$this->notice('当前主模型缺少有效关联主字段，已略过关联操作');
                return null;
            }
            $sk       = $params['sk'][$index];
            $sks[$sk] = $mks[$mk] = $master[$mk];
        }

        //存在用户自定义关联操作
        if (isset($params['select'])) {
            if (is_callable($params['select'])) {
                return call_user_func_array($params['select'], [$this, $mks]);
            }
            return $params['select']; //非回调则直接返回用户设定值
        }

        $this->Creator->where($sks);
        return $this->fields('*')->select();

    }

    //对单个数据进行格式化
    protected function format($data)
    {
        //依次对多个格式化器处理
        foreach ($this->withParams['f'] as $name) {
            $formatter = $this->params['formatter'][$name] ?? null;
            if ($formatter) {
                $data = call_user_func($formatter, $data);
            }
        }
        return $data;
    }

    protected function reset()
    {
        if ($this->backup) {
            $this->params = $this->backup;
        }
        $this->backup     = null;
        $this->withParams = ['f' => [], 'r' => [], 'm' => []];
        $this->isRoot     = true;
        $this->Creator->reset();
    }

}
