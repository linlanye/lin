<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-03 14:37:16
 * @Modified time:      2018-09-03 14:48:19
 * @Depends on Linker:  None
 * @Description:        session测试用
 */

$config  = Linker::Config()::lin('session.server.sql');
$table   = $config['table'];
$id      = $config['fields']['id'];
$content = $config['fields']['content'];
$time    = $config['fields']['time'];
$detail  = "`$id` char(32), `$content` text, `$time` int";

return [
    "$table: $detail" => [
    ],
];
