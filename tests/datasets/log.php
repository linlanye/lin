<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-18 16:59:27
 * @Modified time:      2018-07-18 17:24:34
 * @Depends on Linker:  Config
 * @Description:        日记组件测试用
 */

$config  = Linker::Config()::lin('log.server.sql');
$table   = $config['table'];
$name    = $config['fields']['name'];
$type    = $config['fields']['type'];
$content = $config['fields']['content'];
$time    = $config['fields']['time'];
$detail  = "`$name` varchar(255), `$type` varchar(255), `$content` text, `$time` int";

return [
    "$table: $detail" => [
    ],
];
