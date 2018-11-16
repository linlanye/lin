<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-04-25 13:31:03
 * @Modified time:      2018-09-28 14:04:26
 * @Depends on Linker:  Exception
 * @Description:        使用本地文件存储安全信息
 */

namespace lin\security\structure\handler;

use Linker;

class LocalHandler
{
    private $path;
    private $gc;

    public function __construct($config, $gc)
    {
        $this->path = rtrim($config['path'], '/') . '/';
        if (!file_exists($this->path) && !mkdir($this->path, 0750, true)) {
            Linker::Exception()::throw ('目录创建失败', 1, 'Security', $this->path);
        }
        $this->gc = $gc;
    }
    public function read($filename)
    {
        $filename = $this->path . $filename;
        if (!file_exists($filename) || !($data = file_get_contents($filename))) {
            return [];
        }
        return json_decode($data, true);
    }
    public function write($data)
    {
        foreach ($data as $filename => $content) {
            file_put_contents($this->path . $filename, json_encode($content));
        }
    }

    //写临时客户端
    public function writeTmp($tmp_file, $tmp_data)
    {
        if ($tmp_data) {
            file_put_contents($this->path . $tmp_file, json_encode($tmp_data));
        } else {
            @unlink($tmp_file);
        }
        $this->gc();
    }
    //垃圾回收，只针对临时客户端
    private function gc()
    {

        if (!$this->gc['probability']) {
            return; //不做垃圾回收
        }

        $p = 1 / $this->gc['probability'];
        if (mt_rand(1, $p) != $p) {
            return; //未命中
        }

        $list = glob($this->path . '_tmp_*');
        if (empty($list)) {
            return;
        }
        $life = $this->gc['max_life'];
        foreach ($list as $filename) {
            if (filemtime($filename) + $life < time()) {
                unlink($filename);
            }
        }
    }

}
