<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-10-18 10:55:16
 * @Modified time:      2018-11-02 13:55:57
 * @Depends on Linker:  Config Exception
 * @Description:        使用PDO类实现sql服务器访问
 */

namespace lin\basement\server\sql;

use Linker;
use lin\basement\server\sql\structure\Debug;
use lin\basement\server\structure\RWSServer;
use PDO;
use PDOException;

class SQLPDO extends RWSServer
{
    /*****basement*****/
    use \basement\ServerSQL;

    //返回关联数组
    public function fetchAssoc():  ? array
    {
        $r = $this->DriverStatement->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
    //返回对象
    public function fetchObject(string $className = 'stdClass', array $constructorArgs = []):  ? object
    {
        $r = $this->DriverStatement->fetchObject($className, $constructorArgs);
        return $r ?: null;
    }

    //返回所有数组，数组中每一个元素都为结果数组
    public function fetchAllAssoc():  ? array
    {
        $r = $this->DriverStatement->fetchAll(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
    //返回所有数组，数组中每一个元素都为结果对象
    public function fetchAllObject(string $className = 'stdClass', array $constructorArgs = []):  ? array
    {
        $r = $this->DriverStatement->fetchAll(PDO::FETCH_CLASS, $className, $constructorArgs);
        return $r ?: null;
    }

    //返回新插入记录的主键
    public function lastID(string $name = ''):  ? string
    {
        $this->checkWDriver();
        if (!$name) {
            $name = null;
        }
        try {
            $r = $this->rwDriver['w']->lastInsertId($name); //有些数据库不支持
        } catch (PDOException $e) {
            $r = null;
        }
        return $r ?: null;
    }
    //返回影响的记录数
    public function rowCount(): int
    {
        return $this->DriverStatement->rowCount();
    }

    /**
     * sql语句执行入口
     * @param  string $sql        sql语句
     * @param  array  $params     sql语句中的绑定变量
     * @return void
     */
    public function execute(string $sql, array $params = []): bool
    {
        $t1   = microtime(true);
        $sql  = trim($sql); //去除两端空格
        $type = strtoupper(substr($sql, 0, 6));
        //根据操作类型选择服务器，只有非事务的select使用读服务器
        if ($type == 'SELECT' && !$this->isTrans) {
            $this->setDriver('r');
        } else {
            $this->setDriver('w');
        }

        $this->DriverStatement = $this->Driver->prepare($sql);
        if (!$this->DriverStatement) {
            $this->exception('预处理语句失败', $sql);
        }

        //绑定变量
        if (is_numeric(key($params))) {
            foreach ($params as $k => $v) {
                $this->DriverStatement->bindValue($k + 1, $v); //使用?占位符,索引从1开始
            }
        } else {
            foreach ($params as $k => $v) {
                $this->DriverStatement->bindValue($k, $v);
            }
        }

        //查询结果并记录时间
        $r    = $this->DriverStatement->execute();
        $time = microtime(true) - $t1;

        //抛出错误
        if ($r === false) {
            $error = $this->DriverStatement->errorInfo();
            $error = '[SQLSTATE ' . $error[0] . ']' . $error[1] . ' ' . $error[2];
            $this->exception($error);
            return false;
        }

        //调试打开，输送执行信息
        if ($this->Debug) {
            $type = $type == 'SELECT' ? 'read' : 'write';
            $this->Debug->execute($sql, $time, $this->index, $this->DriverStatement->rowCount(), $type, $params); //执行语句，绑定变量，执行时间，执行服务器索引
        }
        return true;
    }

    public function beginTransaction(): bool
    {
        $this->checkWDriver();
        $time = microtime(true);
        $r    = $this->rwDriver['w']->beginTransaction(); //事务使用写服务器
        if ($this->Debug) {
            $this->Debug->execute('begin transaction', microtime(true) - $time, $this->rwIndex['w'], $r, 'write');
        }
        $this->isTrans = true;
        return $r;
    }
    public function rollBack(): bool
    {
        $this->checkWDriver();
        $time = microtime(true);
        $r    = $this->rwDriver['w']->rollBack();
        if ($this->Debug) {
            $this->Debug->execute('<font style="color:red">roll back</font>', microtime(true) - $time, $this->rwIndex['w'], $r, 'write');
        }
        $this->isTrans = false;
        return $r;
    }
    public function commit(): bool
    {
        $this->checkWDriver();
        $time = microtime(true);
        $r    = $this->rwDriver['w']->commit();
        if ($this->Debug) {
            $this->Debug->execute('commit', microtime(true) - $time, $this->rwIndex['w'], $r, 'write');
        }
        $this->isTrans = false;
        return $r;
    }
    public function inTransaction(): bool
    {
        if ($this->rwDriver['w'] && $this->rwDriver['w']->inTransaction()) {
            return true;
        }
        return false;
    }
    /*****************/

    private $DriverStatement; //当前PDOStatment对象
    private $isTrans = false;
    public function __construct($DriverOrIndex = null)
    {
        $config      = Linker::Config()::get('lin')['server']['sql'];
        $this->Debug = $config['debug'] ? new Debug : null;
        $this->init('sql', $DriverOrIndex, $config['use']);
    }

    //根据使用的服务器索引获得驱动
    protected function newConnection(array $config, $index):  ? object
    {
        try {
            $PDO = new PDO($config['dsn'], $config['user'], $config['pwd']); //如果未被实例化，则实例化后并缓存
        } catch (PDOException $e) {
            $this->exception('服务器连接失败', $index . '; ' . $e->getMessage());
        }
        return $PDO;
    }
    //异常自动回滚
    protected function exception($msg, $subMsg = '', $code = 1)
    {
        if ($this->isTrans) {
            $this->rollBack();
        }
        parent::exception($msg, $subMsg, $code);
    }
    //设置服务器
    private function setDriver($type)
    {
        if (!$this->rwDriver[$type]) {
            $this->exception('缺少可用服务器');
        }
        $this->Driver = $this->rwDriver[$type];
        $this->index  = $this->rwIndex[$type];
    }
    //检查写服务器
    private function checkWDriver()
    {
        if (!$this->rwDriver['w']) {
            $this->exception('缺少可用服务器');
        }
    }

}
