<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-11-06 21:30:26
 * @Modified time:      2018-11-13 09:44:18
 * @Depends on Linker:  none
 * @Description:        sql条件语句生成，每一次实例化只能使用一次
 */

namespace lin\orm\structure;

use Closure;
use lin\orm\structure\SQLBaseParser;

class SQLConditionParser extends SQLBaseParser
{
    private $operator = [ //所有操作符
        '='          => '=', 'eq'   => '=',
        '>'          => '>', 'gt'   => '>',
        '<'          => '<', 'lt'   => '<',
        '>='         => '>=', 'ge'  => '>=',
        '<='         => '<=', 'le'  => '<=',
        '!='         => '!=', 'neq' => '!=', '<>' => '<>',
        'like'       => ' LIKE ',
        'notlike'    => ' NOT LIKE ',
        'in'         => ' IN ',
        'notin'      => ' NOT IN ',
        'exists'     => 'EXISTS ', //exits之前无字段
        'notexists'  => 'NOT EXISTS ',
        'between'    => ' BETWEEN ',
        'notbetween' => ' NOT BETWEEN ',
        'isnull'     => ' IS NULL',
        'notnull'    => ' IS NOT NULL',
    ];

    //处理入口
    public function handle(array $conditions)
    {
        $sql = $this->recursiveHandle($conditions);
        $r   = [' ' . $sql, $this->params];
        $this->reset();
        return $r;
    }

    private function recursiveHandle($conditions)
    {
        $sql = '';
        foreach ($conditions as $field => $value) {
            if (is_numeric($field)) {
                if (is_array($value)) {
                    $sql .= '(' . $this->recursiveHandle($value) . ') AND '; //多个条件单元
                    continue;
                }
                if (trim(strtoupper($value)) === 'OR') {
                    $sql = rtrim($sql, ' AND ') . ' OR ';
                    continue;
                }
                if (preg_match('/:(is)|(not)null/i', $value)) {
                    $field = $value; //尝试匹配isnull和notnull这种无有效键值的特殊情况，如[field:isNull,]
                    $value = 1; //随意取值，只要格式形如['field:isNull'=>v]就可以
                }

            }
            $sql .= $this->parse($field, $value); //一个条件单元
            $sql .= ' AND ';

        }
        return rtrim($sql, ' AND ');
    }

    //解析最小单位的条件语句
    private function parse($fields, $value)
    {

        //获取字段和操作符如'field:gt'
        $fields = array_map('trim', explode(':', $fields));
        if (isset($fields[1])) {
            $op = strtolower($fields[1]);
        } else {
            $op = '=';
        }
        if (!isset($this->operator[$op])) {
            $this->exception('操作符不存在', "$op");
        }
        $fields = array_unique(array_map('trim', explode(',', $fields[0]))); //存在多个字段同一个条件，形如['field1,field2'=>1]

        //exist操作符只能为子句且无需字段信息
        if ($op === 'exists' || $op === 'notexists') {
            if ($value instanceof Closure) {
                $value = '(' . $this->Creator->getSQLFromClosure($value) . ')';
            }
            return $this->operator[$op] . $value;
        }
        $field = $fields[0];
        switch ($op) {
            case 'between':
            case 'notbetween':
                $l        = $this->bindParam($field, current($value)); //多个字段同一个值，绑定第一个即可
                $r        = $this->bindParam($field, next($value));
                $sentence = "$l AND $r";
                break;
            case 'isnull':
            case 'notnull':
                $sentence = '';
                break;
            default:
                //子句情况
                if ($value instanceof Closure) {
                    $sentence = '(' . $this->Creator->getSQLFromClosure($value) . ')';
                    break;
                }
                //in查询
                if ($op === 'in' || $op === 'notin') {
                    $sentence = '(';
                    foreach ($value as $v) {
                        $sentence .= $this->bindParam($field, $v) . ', ';
                    }
                    $sentence = rtrim($sentence, ', ') . ')'; //去掉多余逗号
                    break;
                }
                if (preg_match('/\(/', $field)) {
                    $field = preg_replace('/\((.+?)\)/', '_lin', $field); //存在聚合字段替换括号，绑定变量名
                }
                $sentence = $this->bindParam($field, $value); //其他操作类型

        }

        $op  = $this->operator[$op];
        $sql = '';
        foreach ($fields as $field) {
            $sql .= "$field$op$sentence AND ";
        }
        return rtrim($sql, ' AND ');
    }

}
