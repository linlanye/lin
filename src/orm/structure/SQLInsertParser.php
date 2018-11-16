<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-11-30 21:34:37
 * @Modified time:      2018-11-07 09:06:22
 * @Depends on Linker:  none
 * @Description:        sql插入语句生成，每次实例化只适合使用一次
 */

namespace lin\orm\structure;

use Closure;
use lin\orm\structure\SQLBaseParser;

class SQLInsertParser extends SQLBaseParser
{
    //处理入口
    public function handle(array $data)
    {
        if (current($data) instanceof Closure) {
            $this->sql = ' (' . $this->Creator->getSQLFromClosure(current($data)) . ')'; //首元素为闭包才可解析子句
            if (!is_numeric(key($data))) {
                $this->sql = $this->getFields($data) . $this->sql; //存在指定字段的子句
            }
        } else {
            //判断插入类型,插入类型和批量插入标记只以第一个元素为准
            $current = current($data);

            if (is_array($current)) {
                $key   = key($current);
                $multi = true;
                $data  = $this->format($data, $multi);
            } else {
                $key   = key($data);
                $multi = false;
                $data  = $this->format($data, $multi);
            }

            if (is_numeric($key)) {
                $this->parse($data, $multi); //解析无字段数据
            } else {
                $this->parseWithFields($data, $multi);
            }
        }
        $r = [$this->sql, $this->params];
        $this->reset();
        return $r;
    }
    //解析未指定插入字段
    private function parse($data, $multi)
    {
        if ($multi) {
            //检查多条数据格式是否一致
            $n = count(current($data));
            next($data);
            foreach ($data as $v) {
                if (count($v) !== $n) {
                    $this->exception('批量插入数据的格式不一致', "every one needs ${n} fields");
                }
            }
            reset($data);
            $values = $this->getMultiValues($data);
        } else {
            $values = $this->getValues($data);
        }

        $this->sql = ' VALUES' . $values;
    }
    //解析指定插入字段
    private function parseWithFields($data, $multi)
    {
        if ($multi) {
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k] = $this->format($v);
                ksort($data[$k], SORT_STRING); //多条数据排序一致
            }

            //检查多条数据格式是否一致
            $all_fields = implode(', ', array_keys(current($data)));
            next($data);
            foreach ($data as $v) {
                if (implode(', ', array_keys($v)) !== $all_fields) {
                    $this->exception('批量插入数据的格式不一致', "every one needs ${all_fields}");
                }
            }
            reset($data);

            //获得字段和值
            $fields = $this->getFields(current($data));
            $values = $this->getMultiValues($data);
        } else {
            $data   = $this->format($data);
            $fields = $this->getFields($data);
            $values = $this->getValues($data);
        }
        $this->sql = $fields . ' VALUES ' . $values;
    }

    //获得数据的字段部分
    private function getFields($data)
    {
        return ' (' . implode(', ', array_keys($data)) . ')';
    }
    //获得数据的值部分
    private function getValues($data)
    {
        $values = '(';
        foreach ($data as $field => $value) {
            $values .= is_null($value) ? 'null' : $this->bindParam($field, $value); //对null值不执行绑定变量，但需占位
            $values .= ', ';
        }
        return rtrim($values, ', ') . ')';
    }
    //获得多数据的值部分
    private function getMultiValues($data)
    {
        $values = '';
        foreach ($data as $value) {
            $values .= $this->getValues($value) . ', ';
        }
        return rtrim($values, ', ');
    }

    //对数据进行预处理，格式化成方便使用的形式
    private function format($data)
    {
        $_data = [];
        foreach ($data as $fields => $value) {
            $fields = array_map('trim', explode(',', $fields)); //拆分多个字段一个值，形如['field1,field2'=>value]
            foreach ($fields as $field) {
                $_data[$field] = $value;
            }
        }
        return $_data;
    }
}
