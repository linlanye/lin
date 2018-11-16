<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2018-09-09 22:40:32
 * @Depends on Linker:  None
 * @Description:        视图类
 */
namespace lin\view;

use Linker;
use lin\view\structure\Parser;

class View
{
    private $data = [];
    private $Parser;

    public function __construct()
    {
        $this->Parser = new Parser($this);
    }

    //批量分配模板变量
    public function withData(array $data): object
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * 以单次方式分配方式，分配模板变量
     * @param  string $name  欲分配的变量名
     * @param  mixed  $value 变量值
     * @return object $this
     */
    public function assign(string $name, $value): object
    {
        $this->data[$name] = $value;
        return $this;
    }

    //显示视图页面
    public function show(string $view): void
    {
        $this->Parser->show(trim($view, '/'), $this->data); //获得解析后值，末尾加入随机字符
        $this->data = [];
    }
    //获得解析内容
    public function getContents(string $view): string
    {
        return file_get_contents($this->getFile($view));
    }
    //获得解析后文件名
    public function getFile(string $view): string
    {
        $file       = $this->Parser->getFile(trim($view, '/'), $this->data); //获得解析后值，末尾加入随机字符
        $this->data = [];
        return $file;
    }
    //获取分配变量
    public function getData(): array
    {
        return $this->data;
    }

}
