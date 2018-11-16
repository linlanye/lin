<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-01-03 17:41:10
 * @Modified time:      2018-11-02 14:14:47
 * @Depends on Linker:  ServerSQL Exception
 * @Description:        用于查询关系数据库的对象化操作。多记录查询时，可使用yieldSelect方法逐次获取结果而节约内存
 */

namespace lin\orm\query;

use Closure;
use Linker;
use lin\orm\structure\SQLCreator;

class Query
{
    private $transResult = []; //自动事务执行结果
    private $isTrans     = false; //事务标记,设置为公共可见方便处理
    private $hasWhere    = false; //是否使用where语句，用于对update和delete做强制限制
    private $isGenerator = false;
    private $Driver; //执行驱动
    private $Creator;
    private static $terminalMethod = [ //返回结果的方法
        'select' => 1, 'one' => 1, 'update' => 1, 'insert' => 1, 'replace' => 1, 'delete' => 1,
        'max'    => 1, 'min' => 1, 'sum'    => 1, 'avg'    => 1, 'count'   => 1,
    ];

    //可外部指定驱动实例
    public function __construct()
    {
        $this->Creator = new SQLCreator(); //实例化sql构件器
        $this->Driver  = Linker::ServerSQL(true); //获得实例
    }

    //动态调用sql生成器方法
    public function __call($method, $args)
    {
        call_user_func_array([$this->Creator, $method], $args); //执行sql生成器的方法

        if (isset(self::$terminalMethod[$method])) {
            $this->Creator->execute(); //执行语句构建
            switch ($method) {
                case 'update':
                case 'delete':
                    if (!$this->hasWhere) {
                        if ($this->isTrans) {
                            $this->Driver->rollBack();
                        }
                        Linker::Exception()::throw ('更新和删除必须存在条件限制', 1, 'ORM Query');
                    }
                case 'insert':
                case 'replace':
                    $this->Driver->execute($this->Creator->getSQL(), $this->Creator->getParameters());
                    if ($this->isTrans) {
                        $this->transResult[] = $this->Driver->rowCount(); //事务记录写操作
                    }
                    break;
                default:
                    $this->Driver->execute($this->Creator->getSQL(), $this->Creator->getParameters());
                    break;
            }
            $this->hasWhere = false;

            switch ($method) {
                case 'replace':
                case 'insert':
                case 'update':
                case 'delete':
                    return $this->Driver->rowCount(); //返回影响的行数
                case 'one':
                    return $this->Driver->fetchAssoc();
                case 'select':
                    if ($this->isGenerator) {
                        $this->isGenerator = false; //关闭生成器开关并返回生成器
                        return $this->generator();
                    }
                    return $this->Driver->fetchAllAssoc();
                default:
                    $r = $this->Driver->fetchAssoc(); //注意聚合查询失败为null，count也为null
                    return $r ? end($r) : $r; //位于最后一个
            }
        }
        if ($method == 'where') {
            $this->hasWhere = true;
        }
        return $this;
    }
    //返回可yield的select查询结果
    function yield (): object{
        $this->isGenerator = true;
        return $this->select();
    }
    public function setDriver(object $Driver): object
    {
        $this->Driver = $Driver;
        return $this;
    }
    public function getDriver(): object
    {
        return $this->Driver;
    }
    public function lastID():  ? string
    {
        return $this->Driver->lastID();
    }
    /**
     * 自动提交回滚事务，只对写操作判断，若任一写失败则回滚，否则提交
     * @param  Closure $Closure 内含操作的闭包，入参为自身
     * @return mixed            返回执行成功与否
     */
    public function autoTrans(Closure $Closure) : bool
    {
        $this->Driver->beginTransaction();
        $this->isTrans = true; //事务开始标记
        $r             = $Closure($this); //闭包只有一层

        //执行事务
        $success = true;
        foreach ($this->transResult as $result) {
            if (!$result) {
                $success = false;
                break;
            }
        }
        if ($success) {
            $this->Driver->commit();
        } else {
            $this->Driver->rollBack(); //若任一操作执行失败，则自动回滚
        }

        $this->transResult = []; //清空事务结果
        $this->isTrans     = false;
        return $success;
    }

    //返回生成器，用于select查询多记录时，生成器返回查询的记录总数
    private function generator()
    {
        $n = 0;
        while ($r = $this->Driver->fetchAssoc()) {
            yield $r;
            $n++;
        }
        return $n;
    }
}
