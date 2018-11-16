<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-08-03 10:29:05
 * @Modified time:      2018-09-28 21:19:22
 * @Depends on Linker:  Config Exception
 * @Description:        验证器，规则格式如field=>['callFunc','errorInfo', field=>callFunc, 'field1,field2'=>callFunc，
 *                      对与需要传参的情况使用冒号表明，多个参数用“,”隔开，对单字段多规则验证使用闭包完成，
 *                      对规则可选关键字为must,should,may意味着字段必须验证，字段存在才验证，字段存在且不为非0空值才验证
 *                      使用方式如field:must=>callFunc，所有的规则和验证方法只能定义在子类的属性或方法中
 */
namespace lin\validator;

use Linker;
use lin\validator\structure\Debug;
use lin\validator\structure\Params;

class Validator
{
    protected $_error_info_80128 = []; //验证未通过字段的错误信息
    protected $_rules_13077      = [];
    protected $_config_46007; //配置
    protected $_Debug_12808;
    final public function __construct()
    {
        if (Params::none(static::class)) {
            $this->setting();
        }
        $this->_config_46007 = Linker::Config()::get('lin')['validator'];
        if ($this->_config_46007['debug']) {
            $this->_Debug_12808 = new Debug(static::class);
        }
    }

    //加载内置验证方法
    final public function __call($func, $args)
    {
        if (method_exists('\lin\validator\structure\Functions', $func)) {
            return call_user_func_array(['\lin\validator\structure\Functions', $func], $args); //\lin\processor\ValidFunctions里的函数
        }
        $this->exception('验证方法不存在', $func); //前两者不存在，则抛出异常
    }

    final public function validate(array $data, bool $weakMode = false): bool
    {
        if (!$this->_rules_13077) {
            return true; //未使用规则直接通过
        }
        $t        = microtime(true);
        $ruleName = $this->_rules_13077;
        $this->reset(); //每次使用前都需重置

        $rules = [];
        foreach ($ruleName as $name) {
            $rule = Params::get(static::class, $name);
            if (!$rule) {
                $this->exception('验证规则不存在', static::class . ': ' . $name);
            }
            $rules[$name] = $rule;
        }

        $once = false; //通过一次的标记，用于弱模式验证
        $call = is_callable($this->_config_46007['default']['info']) ? $this->_config_46007['default']['info'] : false; //失败后记录信息回调

        //遍历规则进行验证，可能存在同一字段多次使用规则的情况
        foreach ($rules as $name => $item) {
            foreach ($item as $field => $rule) {
                $func      = $rule[0];
                $fields    = $rule[1];
                $type      = $rule[2] ?: $this->_config_46007['default']['type'];
                $error_msg = $rule[3];
                $args      = [];
                $exists    = true;
                foreach ($fields as $_field) {
                    if (!isset($data[$_field]) && !array_key_exists($_field, $data)) {
                        $exists = false; //检查参数是否存在，多个参数情况下缺少任一都不行
                        break;
                    }
                    $args[] = $data[$_field];
                }
                $r = $this->isValid($func, $args, $exists, $type);

                if ($r) {
                    $once = true;
                } else {
                    if ($error_msg === null && $call) {
                        $error_msg = call_user_func_array($call, $fields);
                    }
                    $this->_error_info_80128[$field] = $error_msg; //记录失败信息，同一字段多个规则情况下以最后一个规则为主
                }
                if ($this->_Debug_12808) {
                    $this->_Debug_12808->validate($field, $name, $r, microtime(true) - $t);
                    $t = $t = microtime(true); //更新下一次时间起点
                }
                if (!$r && !$weakMode) {
                    return false; //严格模式下直接中断
                }
            }
        }
        return $once; //弱模式下存在字段通过即可
    }

    final public function withRule(string $ruleName): object
    {
        $ruleName           = array_map('trim', explode(',', $ruleName));
        $this->_rules_13077 = array_merge($this->_rules_13077, $ruleName);
        return $this;
    }
    final public function getErrors(): array
    {
        return $this->_error_info_80128;
    }

    //重置数据
    final public function reset(): bool
    {
        $this->_error_info_80128 = [];
        $this->_rules_13077      = [];
        return true;
    }

    final protected function setRule(string $name, array $rules): bool
    {
        Params::set(static::class, $name, $rules);
        return true;
    }
    final protected function isValid($func, $args, $exists, $type)
    {
        switch ($type) {
            case 'must': //是否存在都验证，不存在则直接不通过，存在则验证
                if (!$exists) {
                    return false;
                }
                break;
            case 'should': //存在情况下验证
                if (!$exists) {
                    return true; //不存在，则本身满足should规则，跳过验证，返回true
                }
                break;
            default: //存在且非空情况下验证
                if (!$exists) {
                    return true;
                }
                $skip = true;
                foreach ($args as $value) {
                    if ($this->notEmpty($value)) {
                        $skip = false; //多个参数必须同时为空，才可跳过may规则
                        break;
                    }
                }
                if ($skip) {
                    return true;
                }
        }
        if (is_callable($func)) {
            return call_user_func_array($func, $args);
        }
        return call_user_func_array([$this, $func], $args); //调用当前类中定义的验证方法

    }
    final protected function notEmpty($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        return is_numeric($value) || !empty($value); //不为0,'0'的空值
    }

    final protected function exception($info, $subInfo = '')
    {
        $this->reset();
        Linker::Exception()::throw ($info, 1, 'Validator', $subInfo);
    }

    protected function setting()
    {}
}
