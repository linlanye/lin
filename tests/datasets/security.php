<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-28 11:25:22
 * @Modified time:      2018-09-28 11:28:06
 * @Depends on Linker:  None
 * @Description:        Security用
 */
$config  = Linker::Config()::lin('security.server.sql');
$table   = $config['table'];
$id      = $config['fields']['id'];
$type    = $config['fields']['type'];
$content = $config['fields']['content'];
$time    = $config['fields']['time'];
$detail  = "`$id` varchar(255), `$type` int, `$content` text, `$time` int";

return [
    "$table: $detail" => [
    ],
];
