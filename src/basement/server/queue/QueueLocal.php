<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-11-28 09:09:26
 * @Modified time:      2018-11-02 13:57:54
 * @Depends on Linker:  Config Exception
 * @Description:        使用本地文件模拟可靠队列服务器，在并发场景下仍可保持数据一致性。
 *                      若需转载或通过其它语言重写本算法实现，请注明原作者为林澜叶，原出处为lin框架
 */
namespace lin\basement\server\queue;

use Closure;
use Linker;
use lin\basement\server\queue\structure\Debug;

class QueueLocal
{
    /*****basement*****/
    use \basement\ServerQueue;
    public function setName($name): bool
    {
        $this->__name = $name;
        return true;
    }

    public function push($data): bool
    {
        return $this->handlePush(serialize($data) . PHP_EOL, 1);
    }
    public function multiPush(array $data): bool
    {
        if (empty($data)) {
            return false; //空数据情况
        }
        $_data = '';
        foreach ($data as $v) {
            $_data .= serialize($v) . PHP_EOL;
        }
        return $this->handlePush($_data, count($data));
    }
    public function pop(int $amount = 1):  ? array
    {
        if (!$this->dataExists()) {
            return null; //队列数据不存在返回null
        }
        return $this->handlePop($amount);
    }
    //队列是否为空
    public function isEmpty() : bool
    {
        if (!$this->dataExists()) {
            return true;
        }
        return $this->readingLogic(function ($fRead) {
            return fgets($fRead) === false; //不可用feof
        });
    }

    //获得队列大小
    public function getSize(): int
    {
        if (!$this->dataExists()) {
            return 0;
        }
        return $this->readingLogic(function ($fRead) {
            $len = 0;
            while (fgets($fRead) !== false) {
                $len++; //此处不能用feof(会多调用一次)
            }
            return $len;
        });

    }
    /******************/
    private static $THRESHOLD = 0x40000000; //进行重整文件的冗余文件大小，不小于0，不可超过PHP_INT_MAX，默认1g
    private static $caches    = [];
    private $Debug;
    public function __construct($name = null)
    {
        $config       = Linker::Config()::get('lin')['server']['queue'];
        $this->__name = $name ?: $config['default']['name']; //此处总会创建默认的队列名
        $this->Debug  = $config['debug'] ? new Debug : null;

        $config     = $config['driver']['local'];
        $this->path = rtrim($config['path'], '/') . '/';

        //检查目录是否存在
        if (!file_exists($this->path) && !mkdir($this->path, 0750, true)) {
            $this->exception('目录创建失败', $this->path);
        }
    }

    /*高级功能*/
    /**
     * 关闭文件
     * @param  bool $current 只关闭当前队列，否则关闭所有队列
     * @return bool          关闭成功
     */
    public function close($current = false): bool
    {
        if ($current) {
            if (isset(self::$caches[$this->__name])) {
                foreach (self::$caches[$this->__name] as $type => $f) {
                    fclose($f);
                }
                unset(self::$caches[$this->__name]);
            }
        } else {
            foreach (self::$caches as $name => $fPointers) {
                foreach ($fPointers as $type => $f) {
                    fclose($f);
                }
            }
            self::$caches = [];
        }
        return true;
    }

    //设置触发冗余文件整理的阈值
    public function setThreshold(int $THRESHOLD): bool
    {
        if ($THRESHOLD > PHP_INT_MAX) {
            $this->exception('冗余文件阈值不可超过PHP_INT_MAX', PHP_INT_MAX);
        }
        self::$THRESHOLD = $THRESHOLD;
        return true;
    }

    //冗余文件整理, 整理文件时候会阻塞读写
    public function maintain($all = false): void
    {
        if ($all) {
            //整理所有队列文件
            $name = $this->__name;
            $list = glob($this->path . '*.lq');
            if ($list) {
                foreach ($list as $file) {
                    $filename = pathinfo($file)['filename']; //循环整理
                    if ($filename == $name) {
                        continue;
                    }
                    $this->__name = $filename;
                    $this->handleMaintain();
                }
            }
            $this->__name = $name; //最后整理当前队列
        }
        $this->handleMaintain();

    }
    /*********/

    private function handlePush($data, $amount)
    {
        $t = microtime(true);
        $this->openFile();
        //检查是否可以写入，若不可写入，尝试整理冗余文件
        $len = strlen($data);
        if ($len + filesize($this->path . $this->__name . '.lq') > PHP_INT_MAX) {
            $this->maintain();
            //文件整理后查看是否可以写入
            if ($len + filesize($this->path . $this->__name . '.lq') > PHP_INT_MAX) {
                $this->exception('无法写入数据，当前队列已满', $this->__name);
            }
        }

        $fLock  = self::$caches[$this->__name]['lock'];
        $fWrite = self::$caches[$this->__name]['write'];
        flock($fLock, LOCK_EX); //写前加锁
        $r = fwrite($fWrite, $data);
        flock($fLock, LOCK_UN);

        if ($this->Debug) {
            $this->Debug->push($this->__name, $amount, microtime(true) - $t, 'local', $r !== false);
        }
        return $r !== false;
    }

    private function handlePop($amount)
    {
        $t = microtime(true);

        //执行弹出数据逻辑
        $r = $this->readingLogic(function ($fRead, $fCursor) use ($amount) {
            $data = fgets($fRead); //读取第一个数据
            if ($data === false) {
                return false;
            }
            $data = [unserialize($data)]; //第一个数据，反序列化可以自动剔除PHP_EOL
            //读取多个数据
            for ($i = 1; $i < $amount; $i++) {
                $tmp = fgets($fRead);
                if ($tmp === false) {
                    $amount = $i;
                    break; //到末尾跳出
                }
                $data[$i] = unserialize($tmp);
            }
            $current = ftell($fRead);
            fwrite($fCursor, $current); //将偏移写入首行，偏移总是增加，无需担心位长错误
            return [$data, $current];
        });

        if ($this->Debug) {
            $this->Debug->pop($this->__name, $amount, microtime(true) - $t, 'local', $r !== false);
        }

        //尝试重整文件
        if ($r && $r[1] >= self::$THRESHOLD) {
            $this->maintain();
        }

        return $r === false ? null : $r[0];
    }

    private function handleMaintain()
    {
        $this->openFile();

        $fRead   = self::$caches[$this->__name]['read'];
        $fCursor = self::$caches[$this->__name]['cursor'];
        $fWrite  = self::$caches[$this->__name]['write'];
        $fLock   = self::$caches[$this->__name]['lock'];
        $ftmp    = $this->open($this->path . $this->__name . '.lq', 'r+'); //用于写的指针

        //对读写都加锁
        fLock($fCursor, LOCK_EX);
        fLock($fLock, LOCK_EX);

        rewind($fCursor); //指针移回开头
        fscanf($fCursor, "%d", $current); //读取偏移
        fseek($fRead, $current, SEEK_SET); //移动到偏移量位置

        //开始重整文件
        $block = 16 * 1024 * 1024; //一次读入16M
        $size  = 0;
        while (!feof($fRead)) {
            $content = fread($fRead, $block);
            fwrite($ftmp, $content); //写入
            $size += $block;
        }
        $size = $size - $block + strlen($content); //最后一次读取内容长度不一定等于block

        //重写pop，push文件
        ftruncate($ftmp, $size); //删除数据文件多余内容
        fclose($ftmp);

        ftruncate($fCursor, 0);
        rewind($fCursor);
        fWrite($fCursor, 0); //重写游标文件

        rewind($fWrite); //读写位置复位，写位置用'a'打开，rewind后指向文件末尾而不是开头
        rewind($fRead);

        //释放锁
        flock($fCursor, LOCK_UN);
        flock($fLock, LOCK_UN);

    }

    //执行读取数据文件的逻辑
    private function readingLogic(Closure $Closure)
    {
        $this->openFile();
        $fCursor = self::$caches[$this->__name]['cursor'];
        $fRead   = self::$caches[$this->__name]['read'];

        fLock($fCursor, LOCK_EX); //读前先加锁
        rewind($fCursor); //指针移回开头
        fscanf($fCursor, "%d", $current); //读取偏移
        fseek($fRead, $current, SEEK_SET); //移动到偏移量位置
        rewind($fCursor);
        $r = call_user_func_array($Closure, [$fRead, $fCursor]);
        fLock($fCursor, LOCK_UN); //解锁

        return $r;
    }

    //打开文件并缓存其句柄
    private function openFile()
    {
        if (isset(self::$caches[$this->__name])) {
            return;
        }
        $queue      = $this->path . $this->__name;
        $dataFile   = $queue . '.lq';
        $lockFile   = $queue . '.locklq';
        $cursorFile = $queue . '.cursorlq';
        if (!file_exists($dataFile)) {
            file_put_contents($dataFile, null); //数据文件不存在，先创建数据文件
        }
        if (!file_exists($lockFile)) {
            file_put_contents($lockFile, 0); //锁文件不存在，先创建
        }
        if (!file_exists($cursorFile)) {
            file_put_contents($cursorFile, null); //游标文件不存在，先创建游标文件
        }
        self::$caches[$this->__name] = [
            'write'  => $this->open($dataFile, 'a'), //写数据文件
            'lock'   => $this->open($lockFile, 'r'), //用于写数据时加锁
            'read'   => $this->open($dataFile, 'r'), //读数据文件
            'cursor' => $this->open($cursorFile, 'r+'), //游标，用于将文件指针移动正确位置，并且提供读数据时加锁
        ];
    }
    //打开文件
    private function open($file, $type)
    {
        $f = fopen($file, $type);
        if (!$f) {
            $this->exception('文件打开失败', $file);
        }
        return $f;
    }
    //主数据文件是否存在
    private function dataExists()
    {
        return file_exists($this->path . $this->__name . '.lq');
    }
    private function exception($msg, $subMsg)
    {
        Linker::Exception()::throw ($msg, 1, 'Server-QueueLocal', $subMsg);
    }
}
