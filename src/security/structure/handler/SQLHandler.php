<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-04-25 13:31:03
 * @Modified time:      2018-09-28 14:51:12
 * @Depends on Linker:  ServerSQL
 * @Description:        使用sql服务器存储安全信息
 */
namespace lin\security\structure\handler;

use Linker;

class SQLHandler
{
    //表参数
    private $table;
    private $id;
    private $content;
    private $time;
    private $type;

    private $gc;
    private $Driver;

    public function __construct($config, $gc)
    {
        $this->id      = $config['fields']['id'];
        $this->content = $config['fields']['content'];
        $this->time    = $config['fields']['time'];
        $this->type    = $config['fields']['type'];
        $this->table   = $config['table'];

        $this->gc     = $gc;
        $this->Driver = Linker::ServerSQL(true);
    }

    public function read($id)
    {
        $sql = "SELECT `$this->content` FROM `$this->table` WHERE `$this->id`=:id LIMIT 1";
        $this->Driver->execute($sql, [':id' => $id]);
        $r = $this->Driver->fetchAssoc();
        if (!$r) {
            return []; //无数据
        }
        return json_decode(current($r), true);
    }

    public function write($data, $type = 0)
    {
        $this->Driver->beginTransaction();
        foreach ($data as $id => $item) {
            if (!$this->handleUpdate($id, $item)) {
                $this->handleInsert($id, $item, $type); //更新失败后才插入
            }
        }
        $this->Driver->commit();
    }
    public function writeTmp($tmp_id, $tmp_data)
    {
        if ($tmp_data) {
            $this->write([$tmp_id => $tmp_data], 1);
        } else {
            $sql = "DELETE FROM `$this->table` WHERE `$this->id`=:id";
            $this->Driver->execute($sql, [':id' => $tmp_id]); //无数据则进行删除
        }
        $this->gc();
    }

    //处理插入，临时客户端时间不为0
    private function handleInsert($id, $data, $type)
    {
        $sql    = "INSERT INTO `$this->table` (`$this->id`, `$this->content`, `$this->time`, `$this->type`) VALUES (:id, :content, :time, $type)";
        $params = [':id' => $id, ':content' => json_encode($data), ':time' => time()];
        $this->Driver->execute($sql, $params);
        return $this->Driver->rowCount();
    }
    private function handleUpdate($id, $data)
    {
        $sql    = "UPDATE `$this->table` SET `$this->content`=:content, `$this->time`=:time WHERE `$this->id`=:id";
        $params = [':id' => $id, ':content' => json_encode($data), ':time' => time()];
        $this->Driver->execute($sql, $params);
        return $this->Driver->rowCount();
    }

    //垃圾回收，只针对临时客户端,需使用事务，防止更新冲突
    private function gc()
    {
        if (!$this->gc['probability']) {
            return; //不做垃圾回收
        }

        $p = 1 / $this->gc['probability'];
        if (mt_rand(1, $p) != $p) {
            return; //未命中
        }

        $expired = time() - $this->gc['max_life'];
        $sql     = "DELETE FROM `$this->table` WHERE  `$this->time`<$expired and `$this->type`=1";
        $this->Driver->beginTransaction();
        $this->Driver->execute($sql);
        $this->Driver->commit();
    }

}
