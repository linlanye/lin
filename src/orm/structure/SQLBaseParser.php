<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-12-02 15:17:52
 * @Modified time:      2018-08-29 16:58:39
 * @Depends on Linker:  Exception
 * @Description:        sql生成器基类
 */

namespace lin\orm\structure;

use Linker;
use lin\orm\structure\SQLCreator;

class SQLBaseParser
{
    protected $sql; //sql语句
    protected $params = []; //绑定的参数
    protected $Creator; //sql生成类
    protected static $counter = []; //全局绑定变量计数器,保证绑定变量名唯一

    public function __construct(SQLCreator $Creator)
    {
        $this->Creator = $Creator;
    }
    //记录语句的绑定变量名和值，并返回绑定变量名
    protected function bindParam($field, $value)
    {
        $field = ':L_' . preg_replace('/\./', '__', $field); //加入前缀，预防与其他类产生的绑定变量污染，替换字段中的'.'为下划线
        if (!isset(self::$counter[$field])) {
            self::$counter[$field] = 0;
        }
        $bind_param                = $field . self::$counter[$field]++; //绑定变量名
        $this->params[$bind_param] = $value; //记录绑定变量值
        return $bind_param;

    }
    protected function exception($info, $subInfo)
    {
        $Exception = Linker::Exception();
        throw new $Exception($info, 1, 'ORM Creator', $subInfo);
    }
    protected function reset()
    {
        $this->params = [];
        $this->sql    = null;
    }
}
