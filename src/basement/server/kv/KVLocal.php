<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-12-17 11:22:40
 * @Modified time:      2018-11-02 13:57:01
 * @Depends on Linker:  Config Exception
 * @Description:        使用本地文件读写模拟KV服务器，本算法特点是快读慢写，本类支持并发场景。
 *                      若需转载或通过其它语言重写本算法实现，请注明原作者为林澜叶，原出处为lin框架
 */
namespace lin\basement\server\kv;

use Linker;
use lin\basement\server\kv\structure\Debug;

class KVLocal
{
    /*****basement*****/
    use \basement\ServerKv;

    //过期时间大于当前时间戳时，认为
    public function set(string $key, $rawData, int $life = 0): bool
    {
        if ($life < 0) {
            return $this->delete($key);
        }

        //处理过期时间
        $t = microtime(true);
        if (!$life) {
            $life = $this->maxLife;
        }
        if ($life > 2592000) {
            $life -= time(); //和memcache时间机制保持一致
        }
        $expired = $life + time();

        $key    = $this->prefix . $key;
        $hash   = $this->getHash($key);
        $data   = [];
        $status = $this->readIndex($key, $hash, $data);

        //写数据
        if ($status & self::STATUS['conflict']) {
            $data[$key] = [$expired, $rawData];
            $this->writeConflict($hash, $data); //直接写冲突文件
        } else {
            $expired = pack('l', $expired);
            $info    = $this->writeData($expired . pack('l', $hash) . md5($key, true) . serialize($rawData) . '|', $data); //非冲突直接写数据文件
            $index   = $expired . pack('l', $info['pos']) . pack('C', $info['id']) . md5($key, true);
            $this->writeIndex($hash, $index); //再写索引
        }

        if ($this->Debug) {
            $this->Debug->handleSet($key, $rawData, $life, microtime(true) - $t, 'local', true);
        }
        return true;
    }
    public function get(string $key)
    {
        $t      = microtime(true);
        $key    = $this->prefix . $key;
        $hash   = $this->getHash($key);
        $data   = null;
        $status = $this->readIndex($key, $hash, $data);
        switch ($status) {
            case self::STATUS['index_valid']:
                $data = $this->readData($hash, $data);
                break;
            case self::STATUS['conflict_valid']:
                $data = $data[$key][1];
                break;
            case self::STATUS['conflict_invalid']:
                $this->cleanIndexConflict($hash, $data); //清理该键的冲突文件和索引
            default:
                $data = null;
                break;
        }

        if ($this->Debug) {
            $this->Debug->handleGet($key, $data, microtime(true) - $t, 'local', $status & self::STATUS['valid']);
        }

        return $data;
    }
    public function delete(string $key): bool
    {
        $t      = microtime(true);
        $key    = $this->prefix . $key;
        $hash   = $this->getHash($key);
        $data   = null;
        $status = $this->readIndex($key, $hash, $data);

        $valid = $status & self::STATUS['valid'];
        if ($valid) {
            if ($valid & self::STATUS['index_valid']) {
                $this->writeIndex($hash, pack('l', time() - 1)); //直接写时间过期
            } else {
                $data[$key][0] = time() - 1;
                $this->writeConflict($hash, $data); //写冲突数据
            }
        }

        if ($this->Debug) {
            $this->Debug->handleDelete($key, microtime(true) - $t, 'local', $valid);
        }
        return $valid;
    }
    public function exists(string $key): bool
    {
        $key  = $this->prefix . $key;
        $hash = $this->getHash($key);
        return $this->readIndex($key, $hash) & self::STATUS['valid'];
    }
    public function flush(): bool
    {
        $t = microtime(true);
        $r = $this->removeAllFiles();
        $this->init(); //删除文件后重新初始化
        if ($this->Debug) {
            $this->Debug->handleFlush(microtime(true) - $t, 'local', $r);
        }
        return $r;
    }
    /*****************/

    const INDEX_SIZE = 25; //每个索引数据需要time(4)+pos(4)+文件id(1)+md5(16)=25(字节)，该值不可修改
    const STATUS     = [ //数据状态，
        'conflict_invalid' => 0b0001, 'conflict_valid' => 0b0010, 'conflict' => 0b0011,
        'index_invalid'    => 0b0100, 'index_valid'    => 0b1000, 'index'    => 0b1100,
        'invalid'          => 0b0101, 'valid'          => 0b1010,
    ];
    private static $MAX = [
        'scan_time' => 0.1, //线性扫描数据文件时允许执行的最大时间，单位s
        'file_size' => 0x7fffffff, //数据文件的最大大小
        'hash'      => 85899343, //用于hash索引的最大值,也为hash不冲突情况下的最大键数(质数为佳)，应不大于PHP_INT_MAX/self::INDEX_SIZE
    ];
    private static $BLOCK = [
        'size'   => 107, //一个数据块的基础大小(字节)
        'factor' => 1.2, //数据块大小的增长因子，最多允许增长255次
    ];
    private static $cache = []; //缓存[文件id=>[文件指针,数据块大小]]
    private static $fIndex; //缓存索引指针
    private $path;
    private $prefix;
    private $Debug;
    private $maxLife; //数据的最大生命期
    public function __construct()
    {
        $config        = Linker::Config()::get('lin')['server']['kv'];
        $this->Debug   = $config['debug'] ? new Debug : null;
        $this->prefix  = $config['prefix'];
        $this->path    = rtrim($config['driver']['local']['path'], '/') . '/';
        $this->maxLife = $config['default']['life'];
        if ($this->maxLife > 2592000) {
            $this->maxLife = 2592000; //最长生命，30天,和memcached保持一致
        }
        $this->init(); //初始化
    }
    public function close(): bool
    {
        foreach (self::$cache as $key => $value) {
            fclose($value[0]);
            unset(self::$cache[$key]);
        }
        fclose(self::$fIndex);
        self::$fIndex = null;
        return true;
    }

    /***设置块参数和尺度参数，用于测试***/
    public static function setBlockParameters(array $params)
    {
        self::$BLOCK = array_merge(self::$BLOCK, $params);
    }
    public static function setMaxParameters(array $params)
    {
        self::$MAX = array_merge(self::$MAX, $params);
    }
    /******/

    //初始化，创建索引文件和打开索引文件
    private function init()
    {
        //检查目录是否存在
        if (!file_exists($this->path) && !mkdir($this->path, 0750, true)) {
            $this->exception('目录创建失败', $this->path);
        }

        //初始化索引文件
        $indexFile = $this->path . 'main.indexlkv';
        if (!file_exists($indexFile)) {
            $this->removeAllFiles(); //清理可能存在的数据文件

            //生成索引文件, 文件最大不超过2gb
            $tmp    = pack('C', 0);
            $data   = '';
            $fIndex = $this->open($indexFile, 'w');
            for ($i = 0; $i < self::INDEX_SIZE; ++$i) {
                $data .= $tmp; //生成一个占位索引
            }

            //尝试批量写文件，以16k为单位写
            $unit = 1024 * 16;
            if (self::$MAX['hash'] >= $unit) {
                $batchTimes = intval(self::$MAX['hash'] / $unit);
                $times      = self::$MAX['hash'] % $unit;
                $batchData  = ''; //批量写入的数据块
                for ($i = 0; $i < $unit; ++$i) {
                    $batchData .= $data;
                }
                for ($i = 0; $i < $batchTimes; ++$i) {
                    fwrite($fIndex, $batchData);
                }
            } else {
                $times = self::$MAX['hash'];
            }

            //写入余下不足一个单位的数据
            $batchData = '';
            for ($i = 0; $i < $times; ++$i) {
                $batchData .= $data;
            }
            if ($batchData !== '') {
                fwrite($fIndex, $batchData);
            }
            fclose($fIndex);
        }
        if (!self::$fIndex) {
            self::$fIndex = $this->open($indexFile, 'r+'); //读写模式打开索引
        }
    }

    /**
     * 读取索引信息
     * @param  string $key   目标键
     * @param  int    $hash  该键的hash值
     * @param  mixed &$data  当索引有效时，该值为索引数据，当为索引冲突时，该值为所有冲突数据值
     * @return int           读取状态
     */
    private function readIndex($key, $hash, &$data = null)
    {
        $hash = $this->getHash($key);
        fseek(self::$fIndex, $hash * self::INDEX_SIZE, SEEK_SET); //找到正确位置
        $raw            = fread(self::$fIndex, self::INDEX_SIZE); //读取索引数据
        $index          = unpack('ltime/lpos/Cid', substr($raw, 0, 9));
        $index['check'] = substr($raw, 9);

        //1.未冲突情况
        if ($index['time'] === 0) {
            return self::STATUS['index_invalid']; //空位
        }
        if (md5($key, true) === $index['check']) {
            if ($index['time'] < time()) {
                return self::STATUS['index_invalid']; //过期
            }
            $data = $index; //只在索引可用时记录索引数据
            return self::STATUS['index_valid'];
        }

        //2.冲突情况
        $data = []; //只要冲突就记录冲突数据
        $file = $this->path . $hash . '.conflict.lkv';
        if (!file_exists($file)) {
            return self::STATUS['conflict_invalid']; //无冲突文件
        }
        $data = unserialize(file_get_contents($file)); //存在冲突文件，对data赋值
        if (!isset($data[$key])) {
            return self::STATUS['conflict_invalid']; //无冲突数据
        }
        if ($data[$key][0] < time()) {
            return self::STATUS['conflict_invalid']; //数据已过期
        }
        return self::STATUS['conflict_valid'];
    }
    //写索引
    private function writeIndex($hash, $data)
    {
        fseek(self::$fIndex, $hash * self::INDEX_SIZE, SEEK_SET);
        fwrite(self::$fIndex, $data);
    }
    /**
     * 写冲突数据，会先过滤过期的数据，若过滤非空则写入，空则删除冲突文件
     * @param  int   $hash  冲突键名的hash值
     * @param  array $data  所有要写的冲突数据
     * @return bool         是否还有冲突文件
     */
    private function writeConflict($hash, $data)
    {
        $time = time();
        foreach ($data as $key => $value) {
            if ($value[0] < $time) {
                unset($data[$key]); //删除过期数据
            }
        }
        $conflictFile = $this->path . $hash . '.conflict.lkv';
        if (empty($data)) {
            @unlink($conflictFile);
            return false; //无冲突文件
        }
        file_put_contents($conflictFile, serialize($data));
        return true; //有冲突文件

    }

    /**
     * 读数据
     * @param  int      $hash   原数据key的hash值，用于校验当前读入数据是否为正确数据（有可能被随机寻址覆盖）
     * @param  array    $index  用于读取数据的索引信息
     * @return mixed|null       数据值
     */
    private function readData($hash, $index)
    {
        $f         = $this->getFile($index['id']);
        $blockSize = $f[1];
        $f         = $f[0];

        fseek($f, $index['pos'] * $blockSize, SEEK_SET);
        $data = fread($f, $blockSize);

        $data = substr($data, 0, strrpos($data, '|'));
        if ($index['check'] !== substr($data, 8, 16)) {
            return null; //校验不通过
        }
        return unserialize(substr($data, 24)); //无需校验时间，在读取index时候已经校验过
    }

    /**
     * 写数据文件
     * @param  string $data   写入数据
     * @param  array  $index  旧数据的索引数据,不为空时说明旧数据索引有效
     * @return array          数据写入成功后的位置和文件id
     */
    private function writeData($data, $index)
    {
        $len = strlen($data);
        //校验数据有无超限
        if ($len >= self::$MAX['file_size']) {
            $this->exception('当前数据超过文件大小限制', self::$MAX['file_size'] . 'Byte');
        }
        $id = $this->getID($len);
        $f  = $this->getFile($id);

        $rest = $f[1] - $len;
        if ($rest) {
            $padding = pack('C', 0);
            for ($i = 0; $i < $rest; ++$i) {
                $data .= $padding; //填充
            }
        }
        //原索引有效
        if ($index) {
            $old_id  = $index['id'];
            $old_pos = $index['pos'];
            $old_f   = $this->getFile($old_id);
            fseek($old_f[0], $old_pos * $old_f[1], SEEK_SET);

            //id未变前提下，强制原址覆写，此处不一定是当前key的数据（并发写导致）
            if ($id === $old_id) {
                $old_data = fread($old_f[0], 24);
                $check    = substr($old_data, 8, 16);
                $old_data = unpack('ltime/lhash', substr($old_data, 0, 8));

                //当前位置非当前key且未过期，需强制过期
                if ($check !== $index['check'] && $old_data['time'] >= time()) {
                    $this->writeIndex($old_data['hash'], pack('l', time() - 1));
                }
                fseek($old_f[0], -24, SEEK_CUR);
                fwrite($old_f[0], $data);
                return ['id' => $old_id, 'pos' => $old_pos];
            }
            fwrite($old_f[0], pack('l', time() - 1)); //文件id变换，失效旧id文件的数据
        }
        $pos = $this->searchPos($id, $f[0], $f[1]);
        fseek($f[0], $pos * $f[1], SEEK_SET);
        fwrite($f[0], $data);
        return ['id' => $id, 'pos' => $pos];
    }

    /**
     * 在数据文件中搜索可用位置，可用位置必须为‘已过期’(包括空，强制过期)
     * @param  int      $id        当前数据文件的id
     * @param  resource $fData     当前数据文件的指针
     * @param  int      $blockSize 当前数据文件的块大小
     * @return int                 可用位置，非偏移
     */
    private function searchPos($id, $fData, $blockSize): int
    {
        //1.尾部是否可追加
        fseek($fData, 0, SEEK_END);
        $size      = ftell($fData); //此处不能用filesize，该函数无法实时更新
        $maxBlocks = intval(self::$MAX['file_size'] / $blockSize);

        $currentBlocks = $size / $blockSize;
        if ($currentBlocks < $maxBlocks) {
            return $currentBlocks; //数据文件未超过文件大小上限，则可追加
        }

        //2.数据文件超过限制，随机批量线性扫描，每次批量读入不超过8k
        $unit = 8192;
        if ($blockSize >= $unit) {
            $batchBlocks = 1; //一个数据块大于8k，则等价于单次读写
            $batchSize   = $blockSize;
        } else {
            $batchBlocks = intval($unit / $blockSize); //一个批量块中最多有多少个块
            $batchSize   = $batchBlocks * $blockSize; //一次批量读入整数个块的合计大小
        }
        $start = mt_rand(0, $maxBlocks - 1); //随机起始位置
        fseek($fData, $start * $blockSize, SEEK_SET);

        $times = 0; //读取次数
        $t     = microtime(true);
        do {
            $data = fread($fData, $batchSize); //读入一个批量块，接近文件末尾时候，允许不填满读入
            //解析批量块
            for ($i = 0; $i < $batchBlocks; ++$i, ++$start) {
                $tmp = substr($data, $i * $blockSize, 4); //取每一块的时间部分
                if (!$tmp) {
                    rewind($fData);
                    break; //批量块未填充满，到达文件末尾
                }
                if (unpack('l', $tmp)[1] < time()) {
                    return $start >= $maxBlocks ? $start - $maxBlocks : $start; //过期便可覆写
                }
            }
            if (++$times == $maxBlocks) {
                break; //全部扫描完提前退出
            }
        } while ((microtime(true) - $t) < self::$MAX['scan_time']);

        //3.返回随机位
        $pos = mt_rand(0, $maxBlocks - 1);
        fseek($fData, $pos * $blockSize, SEEK_SET);
        $data = unpack('ltime/lhash', fread($fData, 8));
        if ($data['time'] >= time()) {
            $this->writeIndex($data['hash'], pack('l', time() - 1)); //将原未过期数据占用，需过期原数据的索引
        }
        return $pos;
    }
    /**
     * 尝试清理某个键的索引和冲突文件，当该键冲突文件被删除，且索引数据过期，则将索引数据置空
     * @param  int   $hash 键的hash值
     * @param  array $data 冲突文件数据
     * @return void
     */
    private function cleanIndexConflict($hash, $data)
    {
        if (($data && !$this->writeConflict($hash, $data)) || !$data) {
            fseek(self::$fIndex, $hash * self::INDEX_SIZE, SEEK_SET); //此时无冲突文件
            if (unpack('l', fread(self::$fIndex, 4))[1] < time()) {
                $tmp     = pack('C', 0);
                $nothing = '';
                for ($i = 0; $i < self::INDEX_SIZE; ++$i) {
                    $nothing .= $tmp;
                }
                $this->writeIndex($hash, $nothing); //无冲突文件，且索引数据过期，清空原索引数据
            }
        }
    }
    //打开文件
    private function open($file, $mode = 'rb+')
    {
        $f = fopen($file, $mode);
        if (!$f) {
            $this->exception('文件打开失败', $file);
        }
        return $f;
    }
    //删除所有文件
    private function removeAllFiles()
    {
        $r = false;
        //关闭缓存
        if (self::$cache) {
            foreach (self::$cache as $key => $value) {
                fclose($value[0]);
            }
            self::$cache = [];
        }
        //关闭索引
        if (self::$fIndex) {
            fclose(self::$fIndex);
            self::$fIndex = null;
            $r            = unlink($this->path . 'main.indexlkv');
        }

        $list = glob($this->path . '*.lkv');
        if ($list) {
            foreach ($list as $key => $value) {
                $r = unlink($value);
            }
        }
        return $r;
    }
    //根据数据文件编号，获取文件指针和块大小
    private function getFile($id)
    {
        if (!isset(self::$cache[$id])) {
            $dataFile = $this->path . "$id.lkv";
            if (!file_exists($dataFile)) {
                file_put_contents($dataFile, null); //新建文件
            }
            self::$cache[$id] = [$this->open($dataFile, 'r+'), $this->getBlockSize($id)];
        }
        return self::$cache[$id];
    }
    //根据存入数据长度获得应存入的数据文件编号
    private function getID($len): int
    {
        if ($len <= self::$BLOCK['size']) {
            return 0;
        }
        return ceil(log($len / self::$BLOCK['size'], self::$BLOCK['factor'])); //增长数，也为数据文件编号，向下取整
    }
    //根据数据文件编号获得该数据文件的一个块大小
    private function getBlockSize($id): int
    {
        return ceil(self::$BLOCK['size'] * pow(self::$BLOCK['factor'], $id)); //一个数据块的大小，向上取整
    }

    //采用time33算法
    private function getHash($key)
    {
        $hash = 5381;
        $i    = 0;
        while (isset($key[$i])) {
            $hash += ($hash << 5) + ord($key[$i]);
            if ($hash > self::$MAX['hash'] || $hash < 0) {
                $hash &= self::$MAX['hash']; //模拟溢出
            }
            ++$i;
        }
        return $hash % self::$MAX['hash']; //将hash值映射到索引值中去[0,max_hash)
    }
    private function exception($msg, $subMsg)
    {
        Linker::Exception()::throw ($msg, 1, 'Server-KVLocal', $subMsg);
    }
}
