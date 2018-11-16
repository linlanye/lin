<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-01 10:36:16
 * @Modified time:      2018-11-01 11:28:00
 * @Depends on Linker:  Config Exception ServerSQL
 * @Description:        数据模型映射基类
 */
namespace lin\orm\model\structure;

use Linker;
use lin\orm\model\structure\Debug;
use lin\orm\structure\SQLCreator;

class Map
{
    protected $Driver;
    protected $Creator;
    protected $Debug;
    protected $params; //使用的参数
    protected $backup; //备份的数据
    protected $withParams = ['f' => [], 'r' => [], 'm' => []]; //使用with方法调用的参数
    protected $isRoot     = true;
    protected static $terminal; //终止方法

    public function __construct($ObjOrStr)
    {
        $this->initParams($ObjOrStr);
        $this->Creator = new SQLCreator;
        $this->Driver  = Linker::ServerSQL(true);
        if (Linker::Config()::get('lin')['orm']['debug']) {
            $this->Debug = new Debug;
        }
    }

    //动态调用构建方法
    public function __call($method, $args)
    {
        //优先调用宏
        if (isset($this->params['macro'][$method]) && !in_array($method, $this->withParams['m'])) {
            array_unshift($args, $this); //首个参数为自身
            return call_user_func_array($this->params['macro'][$method], $args);
        }
        if (!method_exists($this->Creator, $method)) {
            $this->exception('未定义的方法', "::$method()");
        }

        call_user_func_array([$this->Creator, $method], $args);
        if (!isset(static::$terminal[$method])) {
            return $this; //非终止方法
        }

        $this->Creator->table($this->params['table']);
        return $this->execute($method, $this->isRoot);
    }

    // 使用指定驱动,需满足basement::ServerSQL
    public function setDriver(object $Driver): object
    {
        $this->Driver = $Driver;
        return $this;
    }
    public function getDriver(): object
    {
        return $this->Driver;
    }

    //加载关联模型,需要加载的关联模型，多个情况下使用,隔开
    public function withRelation(string $relations): object
    {
        $this->withParams['r'] = array_merge($this->withParams['r'], array_map('trim', explode(',', $relations)));
        return $this;
    }
    //本次操作不使用输入或输出数据处理
    public function withFormatter(string $formatters): object
    {
        $this->withParams['f'] = array_merge($this->withParams['f'], array_map('trim', explode(',', $formatters)));
        return $this;
    }

    //本次操作不使用某个宏，使用*表示不使用所有宏
    public function withoutMacro(string $macros): object
    {
        $this->withParams['m'] = array_merge($this->withParams['m'], array_map('trim', explode(',', $macros)));
        return $this;
    }

    protected function execute($method, $isRoot)
    {}

    //初始化参数
    protected function initParams($ModelOrString)
    {}
    //重置使其可以复用
    protected function reset()
    {}
    protected function exception($info, $subInfo = '')
    {
        $this->reset(); //重置便于复用
        Linker::Exception()::throw ($info, 1, 'ORM Model', $subInfo);
    }
    protected function notice($info)
    {
        trigger_error($info, E_USER_NOTICE);
    }
}
