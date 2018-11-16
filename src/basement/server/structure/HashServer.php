<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-12-29 10:10:03
 * @Modified time:      2018-11-02 13:52:30
 * @Depends on Linker:  None
 * @Description:        对数据hash后，根据服务器权值，散列到不同服务器，不区分读写。服务器数量和权值不变情况下，
 *                      数据映射到的服务器不变。
 */
namespace lin\basement\server\structure;

use Linker;

abstract class HashServer
{
    use \lin\basement\server\structure\CommonTrait;

    const MAX_INT                = 0x7fffffff;
    protected static $cacheIndex = []; //缓存的索引
    protected $isOne; //是否为单服务器
    protected $id; //当前实例标志

    //设置可使用的服务器索引和权重
    protected function init($serverName, $DriverOrIndex, $configIndex)
    {
        $this->serverName = $serverName;

        //1.外部指定实例
        if (is_object($DriverOrIndex)) {
            $this->Driver = $DriverOrIndex;
            $this->index  = 'user'; //标记为用户指定的驱动
            $this->isOne  = true;
            return;
        }

        //2.获得指定服务器索引
        if (is_string($DriverOrIndex) || is_int($DriverOrIndex)) {
            $configIndex = $DriverOrIndex; //优先级更高
        }
        if (!isset(self::$cacheIndex[$serverName][$configIndex])) {
            $this->parseServerIndex($configIndex); //解析可使用的服务器索引
        }

        //3.是否为单服务器
        $this->id = $configIndex; //使用索引字符串为id
        if (count(self::$cacheIndex[$serverName][$configIndex]) === 1) {
            $this->isOne = true;
            if (!$this->setConnection(key(self::$cacheIndex[$serverName][$configIndex]))) {
                $this->exception('服务器连接失败');
            }
        }
    }

    //采用一致性hash，选择服务器
    protected function chooseDriver(string $key)
    {
        if ($this->isOne) {
            return true; //单服务器直接返回
        }

        $hash = $this->getHash($key);
        foreach (self::$cacheIndex[$this->serverName][$this->id] as $index => $weight) {
            if ($weight > $hash) {
                if (!$this->setConnection($index)) {
                    unset(self::$cacheIndex[$this->serverName][$this->id][$index]); //删除失败服务器
                    continue; //当前选中服务器连接失败，使用下一个
                } else {
                    break;
                }

            }
        }

        //没有可用服务器
        if (!$this->Driver) {
            $firstIndex = key(self::$cacheIndex[$this->serverName][$this->id]); //第一个服务器可能未尝试
            if (!$this->setConnection($firstIndex)) {
                $this->exception('服务器连接失败');
            }
        }
    }

    //采用time33算法
    protected function getHash($key)
    {
        $hash = 5381;
        $i    = 0;
        while (isset($key[$i])) {
            $hash += ($hash << 5) + ord($key[$i]);
            if ($hash > self::MAX_INT || $hash < 0) {
                $hash &= self::MAX_INT; //模拟溢出
            }
            ++$i;
        }
        return $hash;
    }
    //设置使用的服务器实例和索引
    private function setConnection($index)
    {
        //尝试读取缓存，防止和用户配置键名冲突采用复杂键名
        if (!isset(self::$servers[$this->serverName][$index])) {
            if (!isset(self::$servers[$this->serverName])) {
                self::$servers[$this->serverName] = [];
            }
            $config                                   = Linker::Config()::get('servers')[$this->serverName][$index];
            self::$servers[$this->serverName][$index] = $this->newConnection($config, $index); //调用子类方法获得链接实例
        }
        $this->Driver = self::$servers[$this->serverName][$index];
        $this->index  = $index;
        return !empty($this->Driver);
    }

    private function parseServerIndex($rawIndex)
    {
        //获得可使用索引
        $servers    = Linker::Config()::get('servers')[$this->serverName];
        $indexArray = $this->parseRawIndex($servers, $rawIndex);

        //格式化权重
        $sum = end($indexArray);
        reset($indexArray);
        foreach ($indexArray as $index => $weight) {
            $indexArray[$index] = intval($weight / $sum * self::MAX_INT); //向下取整，将但导致首台服务器的权重偏大一些
        }
        self::$cacheIndex[$this->serverName][$rawIndex] = $indexArray;
    }

}
