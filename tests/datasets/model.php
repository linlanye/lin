<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-18 11:49:56
 * @Modified time:      2018-10-23 14:32:47
 * @Depends on Linker:  None
 * @Description:        ORM Model用
 */
return [
    'users: user_id integer primary key autoincrement, info text, serialize text' => [
        ['info' => md5(mt_rand())],
        ['info' => md5(mt_rand())],
    ],
    'area: city char(255), country char(255), status int'                         => [
        ['city' => 'Yaan', 'country' => 'China', 'status' => 1],
    ],
];
