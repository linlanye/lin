<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-19 17:11:52
 * @Modified time:      2018-11-02 14:13:21
 * @Depends on Linker:  Config Exception
 * @Description:        访问本地文件读写
 */
namespace lin\basement\server\local;

use Linker;
use lin\basement\server\local\structure\Debug;
use Throwable;

class Local
{
    /*****basement*****/
    use \basement\ServerLocal;

    public function setPath(string $path): bool
    {
        $this->__path = rtrim($path, '/') . '/';
        return true;
    }
    //文件最后修改时间
    public function getMTime(string $fileName):  ? int
    {
        $fileName = $this->__path . $fileName;
        if (!file_exists($fileName)) {
            return null;
        }
        return filemtime($fileName);
    }
    //文件最后访问时间
    public function getATime(string $fileName) :  ? int
    {
        $fileName = $this->__path . $fileName;
        if (!file_exists($fileName)) {
            return null;
        }
        return fileatime($fileName);
    }
    //文件创建时间
    public function getCTime(string $fileName) :  ? int
    {
        $fileName = $this->__path . $fileName;
        if (!file_exists($fileName)) {
            return null;
        }
        return filectime($fileName);
    }
    //文件大小
    public function getSize(string $fileName) : int
    {
        $fileName = $this->__path . $fileName;
        if (!file_exists($fileName)) {
            return 0;
        }
        return filesize($fileName);
    }

    //文件是否存在
    public function exists(string $fileName): bool
    {
        return file_exists($this->__path . $fileName);
    }
    //文件是否可写
    public function isWritable(string $fileName): bool
    {
        return is_writable($this->__path . $fileName);
    }
    //文件是否可读
    public function isReadable(string $fileName): bool
    {
        return is_readable($this->__path . $fileName);
    }
    //删除文件
    public function remove(string $fileName): bool
    {
        try {
            if ($fileName) {
                $r = unlink($this->__path . $fileName);
            } else {
                $r = $this->removeDir($this->__path);
            }
        } catch (Throwable $e) {
            $r = false;
        }
        return $r;

    }
    //写文件
    public function write(string $fileName, string $content, string $mode = 'a'): bool
    {
        $time     = microtime(true);
        $fileName = $this->__path . $fileName;
        $dir      = dirname($fileName);
        if (!file_exists($dir) && !mkdir($dir, 0750, true)) {
            $this->exception('目录创建失败', $dir);
        }
        if ($mode === 'a') {
            $r = file_put_contents($fileName, $content, FILE_APPEND); //默认写方式
        } else {
            $f = fopen($fileName, $mode);
            if (!$f) {
                $this->exception('文件打开失败', $fileName);
            }
            $r = fwrite($f, $content);
            fclose($f);
        }
        if ($this->Debug) {
            $this->Debug->write($fileName, $mode, $r !== false, microtime(true) - $time);
        }
        return $r;
    }
    //读文件
    public function read(string $fileName, string $mode = 'r'):  ? string
    {
        $t        = microtime(true);
        $fileName = $this->__path . $fileName;
        if (file_exists($fileName)) {
            if ($mode === 'r') {
                $content = file_get_contents($fileName); //默认读方式
            } else {
                $f = fopen($fileName, $mode);
                if (!$f) {
                    $this->exception('文件打开失败', $fileName);
                }
                $content = fread($f, filesize($f));
                fclose($f);
            }
        } else {
            $content = null;
        }
        if ($this->Debug) {
            $this->Debug->read($fileName, $mode, $content !== null, microtime(true) - $t);
        }
        return $content;
    }
    /******************/
    private $Debug;
    public function __construct($path = null)
    {
        $config       = Linker::Config()::get('lin')['server']['local'];
        $path         = $path ?: $config['path'];
        $this->__path = rtrim($path, '/') . '/';
        $this->Debug  = $config['debug'] ? new Debug : null;
    }

    //删除文件夹，包括里面所有内容
    private function removeDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $list = scandir($dir);
        if (!$list) {
            return false;
        }
        $list = array_flip($list);
        unset($list['.'], $list['..']);

        if (empty($list)) {
            rmdir($dir);
        } else {
            foreach ($list as $file => $nothing) {
                $file = $dir . '/' . $file;
                if (is_dir($file)) {
                    $this->removeDir($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
        return true;
    }

    private function exception($msg, $subMsg)
    {
        Linker::Exception()::throw ($msg, 1, 'Local', $subMsg);
    }
}
