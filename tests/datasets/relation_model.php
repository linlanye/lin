<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-22 14:54:42
 * @Modified time:      2018-10-23 16:27:19
 * @Depends on Linker:  None
 * @Description:        ORM Relation Model用数据
 */

return [
    'master: pk integer primary key autoincrement, mk1 int, mk2 int, placeholder text' => [
        ['mk1' => 1, 'mk2' => 1],
        ['mk1' => 1, 'mk2' => 2],
        ['mk1' => 2, 'mk2' => 1],
        ['mk1' => 2, 'mk2' => 2],
    ],
    'slave: pk integer primary key autoincrement, sk1 int, sk2 int'                    => [
        ['sk1' => 1, 'sk2' => 1],
        ['sk1' => 1, 'sk2' => 2],
        ['sk1' => 2, 'sk2' => 1],
        ['sk1' => 2, 'sk2' => 2],
    ], //非自增
];
