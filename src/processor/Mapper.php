<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-10-24 08:33:55
 * @Modified time:      2018-09-19 20:54:14
 * @Depends on Linker:  None
 * @Description:        数据映射器，使用“.”映射多层数组，如'user.id'=>'id',将$data['user']['id']映为$data['id']
 */

namespace lin\processor;

use lin\processor\structure\Processor;

class Mapper extends Processor
{
    protected $_type_80182 = 'f';

    final public function map(array $data, bool $onlyProcessed = false): array
    {
        if (!$this->_rules_78799) {
            return $data; //未使用规则直接返回元数据
        }
        //获得规则
        $t      = microtime(true);
        $output = []; //输出
        //逐条解析规则
        foreach ($this->_rules_78799 as $name => $rules) {
            $mapped = []; //已映射字段，debug用
            foreach ($rules as $field => $rule) {
                $origin      = $rule[0];
                $deep_origin = $rule[1];
                $targets     = $rule[2];
                $type        = $rule[3]; //格式化类型

                //第一层字段不存在
                if (!isset($data[$origin]) && !array_key_exists($origin, $data)) {
                    if ($type == 'must') {
                        $current  = call_user_func_array($this->_config_66610['default']['value'], [$origin]); //must模式默认赋值
                        $mapped[] = $field;
                        $this->setValue($output, $current, $targets);
                    }
                    continue;
                }
                //第一层字段存在
                $current = $data[$origin];
                if ($type == 'may' && !$this->notEmpty($current)) {
                    continue;
                }
                //无更深字段
                if (!$deep_origin) {
                    unset($data[$origin]); //清除源数据
                    $this->setValue($output, $current, $targets);
                    $mapped[] = $field;
                    continue;
                }

                //存在更深字段
                $prev        = &$data;
                $prev_origin = $origin;
                $skip        = false;
                foreach ($deep_origin as $origin) {
                    if (isset($current[$origin]) || array_key_exists($origin, $current)) {
                        $prev        = &$prev[$prev_origin];
                        $current     = $current[$origin];
                        $prev_origin = $origin;
                    } else {
                        if ($typ == 'must') {
                            $current = call_user_func_array($this->_config_66610['default']['value'], [$origin]); //must模式默认赋值
                            $this->setValue($output, $current, $targets);
                            $mapped[] = $field;
                        }
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    unset($prev[$prev_origin]); //清除源数据
                    $this->setValue($output, $current, $targets);
                    $mapped[] = $field;
                }
            }
            if ($this->_Debug_11837) {
                $this->_Debug_11837->handle($name, $mapped, microtime(true) - $t, 'm');
                $t = microtime(true);
            }
        }
        $this->reset();

        if (!$onlyProcessed) {
            $this->filter($data); //滤掉空值键
            return array_merge_recursive($data, $output);
        }

        return $output;
    }

    //对目标赋值
    final protected function setValue(&$output, $current, $targets)
    {
        foreach ($targets as $target) {
            $reference = &$output;
            foreach ($target as $field) {
                if (!isset($reference[$field]) && !array_key_exists($field, $reference)) {
                    $reference[$field] = [];
                }
                $reference = &$reference[$field];
            }
            $reference = $current;
        }
    }
    //去除掉空值键，用于宽松模式下和源数据合并前用
    final protected function filter(&$data)
    {
        if (!is_array($data)) {
            return;
        }
        foreach ($data as $k => &$v) {
            if (is_array($v)) {
                if (!empty($v)) {
                    $this->filter($v);
                }
                if (empty($v)) {
                    unset($data[$k]);
                }
            }
        }
    }

    final protected function setRule(string $name, array $rawRules): bool
    {
        $rules = [];
        foreach ($rawRules as $fields => $rule) {
            $fields = array_map('trim', explode(':', $fields)); //格式['field:(type)'=>'target','field'=>'target,target2']
            $type   = $fields[1] ?? $this->_config_66610['default']['type'];
            $fields = $fields[0];
            $origin = explode('.', $fields);
            $target = array_map('trim', explode(',', $rule));
            foreach ($target as $key => $value) {
                $target[$key] = explode('.', $value);
            }

            $first_origin = $origin[0];
            unset($origin[0]);
            $rules[$fields] = [$first_origin, $origin, $target, $type];
        }

        if (!isset(self::$_params_98001[$this->_type_80182][static::class])) {
            self::$_params_98001[$this->_type_80182][static::class] = [];
        }
        self::$_params_98001[$this->_type_80182][static::class][$name] = $rules;
        return true;
    }
}
