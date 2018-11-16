<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2018-08-29 16:58:24
 * @Depends on Linker:  ServerSQL
 * @Description:        使用sql服务器存储日志
 */

namespace lin\basement\log\structure;

use Linker;

class SQLHandler
{
    //用于程序运行完毕批量写
    public function write($data, $config)
    {
        $Driver  = Linker::ServerSQL(true);
        $table   = $config['table'];
        $fields  = $config['fields'];
        $name    = $fields['name'];
        $content = $fields['content'];
        $type    = $fields['type'];
        $time    = $fields['time'];
        //采用事务进行批量插入以兼容不同数据库产品
        $fields = "(`$name`, `$content`, `$type`, `$time`)";
        $sql    = "INSERT INTO `$table` $fields VALUES (:name, :content, :type, :time)";

        //事务依次插入，避免有些数据库不支持批量插入
        $Driver->beginTransaction();
        foreach ($data as $logName => $item) {
            $params = [':name' => $item[0], ':content' => $item[2], ':type' => $item[1], ':time' => $item[3]];
            $Driver->execute($sql, $params);
        }
        $Driver->commit();
        return $Driver->rowCount();
    }
}
