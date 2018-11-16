<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-18 11:49:56
 * @Modified time:      2018-07-24 10:38:30
 * @Depends on Linker:  None
 * @Description:        ServerSQL测试专用
 */
return [
    'server_sql:id integer primary key, content varchar(255)' => [
        ['content' => md5(mt_rand())],
        ['content' => md5(mt_rand())],
        ['content' => md5(mt_rand())],
    ],
];
