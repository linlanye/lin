<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-05-22 11:30:56
 * @Modified time:      2018-11-02 14:00:56
 * @Depends on Linker:  Config Exception
 * @Description:        提供模型运作的必须参数，为全静态方法
 */
namespace lin\orm\model\structure;

use Closure;
use Linker;

class Params
{
    private static $data         = []; //用于存储表名和主键，宏，格式化['table'=>表名,'pk'=>'主键名','formatter'=>'数据处理','macro'=>'宏']
    private static $relationData = []; //用于存储关联模型的参数
    private static $config;

    public static function setTable(string $table, $class)
    {
        self::init($class);
        self::$data[$class]['table'] = $table;
    }
    public static function setPK(string $pk, $class)
    {
        self::init($class);
        self::$data[$class]['pk'] = array_map('trim', explode(',', $pk)); //可能存在多主键;
    }
    //设置数据自动处理
    public static function setFormatter(string $formatter, Closure $Closure, $class)
    {
        self::init($class);
        self::$data[$class]['formatter'][$formatter] = $Closure;
    }
    //设置宏
    public static function setMacro(string $macro, Closure $Closure, $class)
    {
        self::init($class);
        self::$data[$class]['macro'][$macro] = $Closure;
    }
    //设置关联属性，不可立即解析，防止table、pk参数未设置完全
    public static function setRelation(string $attr, array $params, $class)
    {
        if (!isset(self::$relationData[$class])) {
            self::$relationData[$class] = [];
        }
        self::$relationData[$class][$attr] = [$params]; //键名为未解析标记
    }

    //补齐表名和主键
    public static function setTableAndPK($class)
    {
        //初始化表名和主键
        self::init($class);
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            $args = [$class, ''];
        } else {
            $args = [substr($class, $pos + 1), substr($class, 0, $pos)]; //[class,namespace]
        }

        if (!self::$data[$class]['table']) {
            if (!is_callable(self::$config['default']['table'])) {
                self::exception('未指定表名');
            }
            self::$data[$class]['table'] = call_user_func_array(self::$config['default']['table'], $args);
        }
        if (!self::$data[$class]['pk']) {
            if (!is_callable(self::$config['default']['pk'])) {
                self::exception('未指定主键');
            }
            self::$data[$class]['pk'] = [call_user_func_array(self::$config['default']['pk'], $args)];
        }
    }
    public static function none($class): bool
    {
        return !isset(self::$data[$class]);
    }

    //获得模型参数
    public static function get($class): array
    {
        $data = self::$data[$class] ?? [];
        if (empty($data)) {
            new $class; //存在无参数情况下(关联操作)，尝试实例化获取设置，参数table,pk始终存在
            $data = self::$data[$class];
        }
        return $data;
    }
    //解析获得关联参数
    public static function getRelation($class, $attr): array
    {
        if (!isset(self::$relationData[$class][$attr])) {
            self::parseRelation([], $attr, $class); //未定义关联参数时，全采用默认解析
        }
        if (!key(self::$relationData[$class][$attr])) {
            self::parseRelation(current(self::$relationData[$class][$attr]), $attr, $class); //已定义但未解析
        }
        return current(self::$relationData[$class][$attr]);
    }

    private static function init($class)
    {
        if (!isset(self::$data[$class])) {
            self::$data[$class] = ['macro' => [], 'formatter' => [], 'table' => '', 'pk' => []];
        }
        if (!self::$config) {
            self::$config                          = Linker::Config()::get('lin')['orm']['model'];
            self::$config['relation']['namespace'] = trim(self::$config['relation']['namespace'], '\\') . '\\';
        }
    }

    private static function parseRelation($params, $attr, $masterClass)
    {
        if (!isset(self::$relationData[$masterClass])) {
            self::$relationData[$masterClass] = [];
        }
        $template = [
            'class' => '', 'mk' => '', 'sk' => '', //必须
            //'select' => null, 'insert' => null, 'update' => null, 'delete' => null, 'merge' => false, //可选
        ];
        $params = array_merge($template, $params);

        //关联类名
        if ($params['class']) {
            if ($params['class'][0] != '\\') {
                $params['class'] = self::$config['relation']['namespace'] . $params['class']; //非根命名空间，加入根命名空间前缀
            }
        } else {
            $params['class'] = self::$config['relation']['namespace'] . $attr; //默认类名为属性名
        }

        //关联主字段
        if ($params['mk']) {
            $params['mk'] = array_map('trim', explode(',', $params['mk']));
        } else {
            $params['mk'] = self::$data[$masterClass]['pk']; //默认为主键，必然被解析
        }
        //关联从字段
        if ($params['sk']) {
            $params['sk'] = array_map('trim', explode(',', $params['sk']));
        } else {
            $slaveClass   = $params['class'];
            $params['sk'] = self::get($slaveClass)['pk']; //默认为主键，此处可能未解析，不可直接调用data数据
        }
        //检查键数目是否一致
        if (count($params['mk']) !== count($params['sk'])) {
            self::exception('关联主字段和从字段数量不一致', implode(',', $params['mk']) . ' | ' . implode(',', $params['sk']));
        }
        self::$relationData[$masterClass][$attr] = ['1' => $params]; //已解析标记
    }
    private static function exception($info, $subInfo = null)
    {
        Linker::Exception()::throw ($info, 1, 'ORM Model', $subInfo);
    }
}
