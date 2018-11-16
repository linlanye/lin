<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-08-30 17:05:27
 * @Modified time:      2018-08-30 17:06:05
 * @Depends on Linker:  None
 * @Description:        测试路由用类
 */
namespace lin\tests\scripts\route;

class SomeClass
{
    public function string()
    {
        echo 'string';
    }
    public function array1()
    {
        echo 'array1';
    }
    public function array2()
    {
        echo 'array2';
    }
    public function main()
    {
        echo 'main';
    }
    public function pre()
    {
        echo 'pre';
    }
    public function post()
    {
        echo 'post';
    }
    public function terminal()
    {
        echo 'terminal';
        return false;
    }
}
