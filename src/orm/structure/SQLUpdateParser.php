<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-11-30 21:56:28
 * @Modified time:      2018-08-29 16:58:42
 * @Depends on Linker:  none
 * @Description:        sql更新语句生成
 */

namespace lin\orm\structure;

use Closure;
use lin\orm\structure\SQLBaseParser;

class SQLUpdateParser extends SQLBaseParser
{
    //处理入口
    public function handle(array $data)
    {
        foreach ($data as $fields => $value) {
            $this->parse($fields, $value);
        }
        $this->sql = ' ' . rtrim($this->sql, ', ');
        $r         = [$this->sql, $this->params];
        $this->reset();
        return $r;
    }
    private function parse($fields, $value)
    {
        $op     = array_map('trim', explode(':', $fields));
        $fields = array_unique(array_map('trim', explode(',', $op[0])));

        if ($value instanceof Closure) {
            $value = '(' . $this->Creator->getSQLFromClosure($value) . ')'; //首元素为闭包才可解析子句
            if (count($fields) > 1) {
                $this->sql .= '(' . implode(',', $fields) . ')=' . $value; //存在指定字段的子句
            } else {
                $this->sql .= $fields[0] . '=' . $value; //存在指定字段的子句
            }
            return;
        }
        $value = $this->bindParam($fields[0], $value); //多个值绑定一个变量即可
        if (isset($op[1])) {
            $op = $op[1];
            foreach ($fields as $field) {
                $this->sql .= "$field=$field$op$value, "; //解析类型如：field=field+1
            }
        } else {
            foreach ($fields as $field) {
                $this->sql .= "$field=$value, ";
            }
        }
    }
}
