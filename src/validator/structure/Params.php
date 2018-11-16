<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-05-22 11:30:56
 * @Modified time:      2018-09-18 16:52:56
 * @Depends on Linker:  None
 * @Description:        存储验证规则
 */
namespace lin\validator\structure;

use Linker;

class Params
{
    private static $data = [];

    //设置规则
    public static function set($class, $ruleName, array $rawRules)
    {
        if (!isset(self::$data[$class])) {
            self::$data[$class] = [];
        }
        $rules = [];
        foreach ($rawRules as $fields => $rule) {
            $fields = array_map('trim', explode(':', $fields)); //格式['field:(type)'=>'func','field,field2'=>[func,'msg']]
            $type   = $fields[1] ?? '';
            $fields = array_map('trim', explode(',', $fields[0]));
            if (is_array($rule)) {
                $func = $rule[0];
                $info = $rule[1] ?? null;
            } else {
                $func = $rule;
                $info = null;
            }
            $rules[implode(', ', $fields)] = [$func, $fields, $type, $info];
        }

        self::$data[$class][$ruleName] = $rules;
    }

    public static function none($class)
    {
        return !isset(self::$data[$class]);
    }

    //获得验证规则
    public static function get($class, $rule): array
    {
        return self::$data[$class][$rule] ?? [];
    }

}
