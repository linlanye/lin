<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-10-24 08:33:55
 * @Modified time:      2018-09-19 21:06:13
 * @Depends on Linker:  None
 * @Description:        格式化器
 */

namespace lin\processor;

use lin\processor\structure\Processor;

class Formatter extends Processor
{
    protected $_type_80182 = 'f';

    final public function format(array $data, bool $onlyProcessed = false): array
    {
        if (!$this->_rules_78799) {
            return $data; //未使用规则直接返回元数据
        }

        //获得规则
        $t      = microtime(true);
        $output = [];

        //逐条解析规则
        foreach ($this->_rules_78799 as $name => $rules) {
            $formatted = []; //debug用
            foreach ($rules as $field => $rule) {
                $func   = $rule[0];
                $fields = $rule[1];
                $type   = $rule[2];

                //获得参数
                $args = [];
                foreach ($fields as $_field) {
                    if (isset($data[$_field]) && array_key_exists($_field, $data)) {
                        $args[] = $data[$_field];
                    } else {
                        if ($type != 'must') {
                            $args = false;
                            break; //非must模式下字段缺一都不格式化
                        }
                        $args[] = call_user_func($this->_config_66610['default']['value'], $_field); //muts模式赋予默认值
                    }
                }
                //may模式下还需验证参数是否为空
                if ($args && $type == 'may') {
                    $skip = true;
                    foreach ($args as $value) {
                        if ($this->notEmpty($value)) {
                            $skip = false; //多个参数必须同时为空，才可跳过may规则
                            break;
                        }
                    }
                    if ($skip) {
                        $args = false;
                    }
                }

                //参数存在才做格式化
                if ($args !== false) {
                    if (!is_callable($func)) {
                        $func = [$this, $func]; //后调用当前类中定义的验证方法
                    }
                    $output[$field] = call_user_func_array($func, $args);
                    $formatted[]    = $field;
                }
            }
            if ($this->_Debug_11837) {
                $this->_Debug_11837->handle($name, $formatted, microtime(true) - $t, 'f');
                $t = microtime(true);
            }
        }
        $this->reset(); //使用后需重置

        //是否只保存处理后的数据
        if (!$onlyProcessed) {
            $output = array_merge($data, $output);
        }

        return $output;
    }

    //加载内置格式化方法
    final public function __call($func, $args)
    {
        if (method_exists('\lin\processor\structure\Functions', $func)) {
            return call_user_func_array(['\lin\processor\structure\Functions', $func], $args); //\lin\processor\ValidFunctions里的函数
        }
        $this->exception('格式化方法不存在', $func);
    }

    final protected function setRule(string $name, array $rawRules): bool
    {
        $rules = [];
        foreach ($rawRules as $fields => $rule) {
            $fields                        = array_map('trim', explode(':', $fields)); //格式['field:(type)'=>func,'field,field2'=>func]
            $type                          = $fields[1] ?? $this->_config_66610['default']['type'];
            $fields                        = array_map('trim', explode(',', $fields[0]));
            $rules[implode(', ', $fields)] = [$rule, $fields, $type];
        }
        if (!isset(self::$_params_98001[$this->_type_80182][static::class])) {
            self::$_params_98001[$this->_type_80182][static::class] = [];
        }
        self::$_params_98001[$this->_type_80182][static::class][$name] = $rules;
        return true;
    }
}
