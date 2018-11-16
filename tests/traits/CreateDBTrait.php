<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-18 16:39:58
 * @Modified time:      2018-09-05 09:05:01
 * @Depends on Linker:  ServerSQL
 * @Description:        提供数据表创建，创建数据来自于datasets
 */
namespace lin\tests\traits;

use Linker;
use lin\orm\query\Query;

trait CreateDBTrait
{
    protected static function createDB($file)
    {
        $Driver = Linker::ServerSQL(true);
        $data   = include __DB__ . "/$file.php";
        $Query  = new Query;
        foreach ($data as $table => $value) {
            $table  = array_map('trim', explode(':', $table));
            $detail = $table[1];
            $table  = $table[0];
            $Driver->execute("drop table if exists $table");
            $Driver->execute("create table $table ($detail)");
            if ($value) {
                $Query->table($table)->insert($value);
            }
        }
    }
}
