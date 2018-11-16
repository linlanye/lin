<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-10-24 15:06:59
 * @Modified time:      2018-09-19 10:02:02
 * @Depends on Linker:  Config Exception
 * @Description:        数据处理器基类
 */
namespace lin\processor\structure;

use Linker;
use lin\processor\structure\Debug;

class Processor
{
    protected $_rules_78799; //当前使用的规则
    protected $_config_66610;
    protected $_type_80182; //当前类类型，格式化器或映射器
    protected $_Debug_11837;
    protected static $_params_98001 = ['f' => [], 'm' => []]; //存储规则

    final public function __construct()
    {
        if (!isset(static::$_params_98001[$this->_type_80182][static::class])) {
            $this->setting();
        }

        $config = Linker::Config()::get('lin')['processor'];
        if ($this->_type_80182 == 'f') {
            $this->_config_66610 = $config['formatter'];
        } else {
            $this->_config_66610 = $config['mapper'];
        }

        if ($config['debug']) {
            $this->_Debug_11837 = new Debug(static::class);
        }
    }

    final public function withRule(string $ruleName): object
    {
        $ruleName = array_map('trim', explode(',', $ruleName));
        if (!isset(self::$_params_98001[$this->_type_80182][static::class])) {
            self::$_params_98001[$this->_type_80182][static::class] = [];
        }

        //遍历获得规则
        foreach ($ruleName as $name) {
            if (!isset(self::$_params_98001[$this->_type_80182][static::class][$name])) {
                if ($this->_type_80182 == 'f') {
                    $info = '格式化规则不存在';
                } else {
                    $info = '映射规则不存在';
                }
                $this->exception($info, static::class . ': ' . $name);
            }
            $this->_rules_78799[$name] = self::$_params_98001[$this->_type_80182][static::class][$name];
        }
        return $this;
    }

    //重置,便于复用
    final protected function reset()
    {
        $this->_rules_78799 = [];
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
        if ($this->_type_80182 == 'f') {
            $type = 'Processor Formatter';
        } else {
            $type = 'Processor Mapper';
        }
        $this->reset();
        Linker::Exception()::throw ($info, 1, $type, $subInfo);
    }

    protected function setting()
    {
    }
}
