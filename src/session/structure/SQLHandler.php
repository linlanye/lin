<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-19 10:32:32
 * @Modified time:      2018-09-03 14:01:28
 * @Depends on Linker:  ServerSQL
 * @Description:        使用sql服务器存储session，对bool返回，不能返回false，否则会抛出警告引起安全隐患
 */
namespace lin\session\structure;

use Linker;
use SessionHandlerInterface;

class SQLHandler implements SessionHandlerInterface
{
    private $life; //有效时间
    private $fields; //字段信息
    private $table; //配置
    private $Driver; //数据库驱动类
    private $isUapte = true; //是否使用更新方式

    public function __construct($config, $life)
    {
        $this->table  = $config['table'];
        $this->fields = $config['fields'];
        $this->life   = (int) $life;
        $this->Driver = Linker::ServerSQL(true);
    }
    public function open($save_path, $session_name): bool
    {
        return true;
    }
    public function read($sessionID): string
    {
        $id      = $this->fields['id'];
        $content = $this->fields['content'];
        $time    = $this->fields['time'];
        $sql     = "SELECT `$content`, `$time` FROM `$this->table` WHERE `$id`=:id LIMIT 1";
        $param   = [':id' => $sessionID];
        $this->Driver->execute($sql, $param); //读取session
        $r = $this->Driver->fetchAssoc();
        if (!$r) {
            $this->isUapte = false; //不存在记录，使用插入方法
            return '';
        }
        //有效期不为0，检查是否过期
        if ($this->life && $r[$time] + $this->life < time()) {
            return '';
        }
        return $r[$content];
    }
    public function write($sessionID, $sessionData): bool
    {
        $id      = $this->fields['id'];
        $content = $this->fields['content'];
        $time    = $this->fields['time'];
        $params  = [':id' => $sessionID, ':content' => $sessionData, ':time' => time()];

        if ($this->isUapte) {
            $sql = "UPDATE `$this->table` SET $content=:content, $time=:time WHERE $id=:id";
        } else {
            $sql = "INSERT INTO `$this->table` (`$id`, `$content`, `$time`) VALUES (:id, :content, :time)";
        }
        $this->Driver->execute($sql, $params);
        return true;
    }

    public function destroy($sessionID): bool
    {
        $id  = $this->fields['id'];
        $sql = "DELETE FROM `$this->table` WHERE `$id`=:id";
        $this->Driver->execute($sql, [':id' => $sessionID]);
        return true;
    }
    public function gc($maxlifetime): bool
    {
        if (!$this->life) {
            return true; //永不过期
        }
        $time    = $this->fields['time'];
        $expired = time() - $this->life;
        //gc必须使用事务，防止遭遇并发的更新操作
        $this->Driver->beginTransaction();
        $sql = "DELETE FROM `$this->table` WHERE `$time`<$expired";
        $this->Driver->execute($sql);
        $this->Driver->commit();
        return true;

    }
    public function close(): bool
    {
        return true; //必须始终返回真值
    }

}
