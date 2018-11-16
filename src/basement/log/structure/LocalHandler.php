<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-22 09:45:55
 * @Modified time:      2018-08-29 16:58:23
 * @Depends on Linker:  ServerLocal
 * @Description:        使用本地文件存储记录日志
 */
namespace lin\basement\log\structure;

use Linker;

class LocalHandler
{
    private $maxSize;
    private $path;
    private $Driver;
    public function write($raw, $config)
    {
        $this->Driver  = Linker::ServerLocal(true);
        $this->maxSize = $config['size'] < 0 ? 0x7fffffff : $config['size'];

        //写入路径
        $path = $config['path'];
        if ($config['frequency'] > 0) {
            if ($config['frequency'] <= 24) {
                $path .= '/' . date('YmdH'); //month
            } else if ($config['frequency'] <= 720) {
                $path .= '/' . date('Ymd'); //day
            } else {
                $path .= '/' . date('Ym'); //hour
            }
        }
        $this->Driver->setPath($path);

        $data = [];
        foreach ($raw as $item) {
            $name = $item[0] . '_';
            if (!isset($data[$name])) {
                $data[$name] = '';
            }
            unset($item[0]);
            $data[$name] .= call_user_func_array($config['format'], $item) . PHP_EOL;
        }
        foreach ($data as $name => $content) {
            $this->handle($name, $content);
        }
        return true;
    }
    private function handle($name, $data)
    {
        $num = 0; //初始标号为0
        if ($this->Driver->exists($name . $num . '.log')) {
            while ($this->Driver->getSize($name . $num . '.log') > $this->maxSize &&
                $this->Driver->exists($name . ++$num . '.log')
            ) {} //得到可用的标号
        }
        $this->Driver->write($name . $num . '.log', $data);
    }

}
