<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-08-08 15:43:00
 * @Modified time:      2018-09-28 10:42:32
 * @Depends on Linker:  Config
 * @Description:        采用读写分离服务器使用的基类。多台带权重的服务器被分为只读和只写。
 *                      每次实例化将按权重随机选择一个只读驱动和一个只写驱动，不同的操作被映射到已选定的只读或只写驱动上。
 *                      注：依赖单独的服务器配置项“servers”。多服务器的数据同步请从服务器自身层面保证，本组件不提供!
 */
namespace lin\basement\server\structure;

use Linker;

abstract class RWSServer
{
    use \lin\basement\server\structure\CommonTrait;

    protected $rwDriver        = ['r' => '', 'w' => '']; //当前使用的读写驱动
    protected $rwIndex         = ['r' => '', 'w' => '']; //使用的服务器索引号
    private static $cacheIndex = []; //缓存解析后使用的服务器索引['severName'=>['rawIndex'=>['index'=>cal_weight]]]

    /**
     * 初始化服务器驱动
     * @param  string                $serverName    服务器名，为servers配置文件中的键名
     * @param  string|int|object     $DriverOrIndex 用户指定的驱动类或服务器索引
     * @param  string $configIndex                  配置文件中默认使用的服务器索引
     * @return void
     */
    protected function init($serverName, $DriverOrIndex, $configIndex)
    {
        $this->serverName = $serverName;

        //1.外部指定实例
        if (is_object($DriverOrIndex)) {
            $this->rwDriver['r'] = $this->rwDriver['w'] = $DriverOrIndex;
            $this->rwIndex['r']  = $this->rwIndex['w']  = 'user'; //标记为用户指定的驱动
            $this->index         = $this->rwIndex['r'];
            $this->Driver        = $this->rwDriver['r']; //默认使用读
            return;
        }

        //2.获得指定服务器索引
        if (is_string($DriverOrIndex) || is_int($DriverOrIndex)) {
            $configIndex = $DriverOrIndex; //优先级更高
        }
        if (!isset(self::$cacheIndex[$serverName][$configIndex])) {
            $this->parseServerIndex($configIndex); //解析可使用的服务器索引
        }

        //3.设置驱动
        $indexWeight = self::$cacheIndex[$serverName][$configIndex];
        if (!empty($indexWeight['r'])) {
            $this->rwIndex['r']  = $this->chooseDriver($indexWeight['r']);
            $this->rwDriver['r'] = self::$servers[$serverName][$this->rwIndex['r']];
        }
        if (!empty($indexWeight['w'])) {
            $this->rwIndex['w']  = $this->chooseDriver($indexWeight['w']);
            $this->rwDriver['w'] = self::$servers[$serverName][$this->rwIndex['w']];
        }

        //4.选择服务器，读优先
        if ($this->rwDriver['r']) {
            $this->index  = $this->rwIndex['r'];
            $this->Driver = $this->rwDriver['r'];
        } else if ($this->rwDriver['w']) {
            $this->index  = $this->rwIndex['w'];
            $this->Driver = $this->rwDriver['w'];
        } else {
            $this->exception('服务器连接失败', 'r: ' . $this->rwDriver['r'] . '; w: ' . $this->$this->rwDriver['w']);
        }

    }

    /**
     * 解析并缓存需要使用的服务器索引
     * @param string $rawIndex   使用的服务器索引表达式，规则见配置文件
     */
    private function parseServerIndex($rawIndex)
    {
        //解析可使用的服务器索引
        $servers    = Linker::Config()::get('servers')[$this->serverName];
        $indexArray = $this->parseRawIndex($servers, $rawIndex);
        //分离读写配置
        $indexRW = ['r' => [], 'w' => []];
        foreach ($indexArray as $index => $weight) {
            switch ($servers[$index]['mode']) {
                case 'r':
                    $indexRW['r'][$index] = $weight;
                    break;
                case 'w':
                    $indexRW['w'][$index] = $weight;
                    break;
                default:
                    $indexRW['r'][$index] = $weight;
                    $indexRW['w'][$index] = $weight;
                    break;
            }
        }
        //缓存
        self::$cacheIndex[$this->serverName][$rawIndex] = $indexRW;
    }

    /**
     * 设置并缓存连接驱动
     * @param array $indexWeight ['index'=>'calculate_weight']
     */
    private function chooseDriver($indexWeight)
    {
        $sum = end($indexWeight);
        reset($indexWeight);
        $threshold = mt_rand(1, $sum); //权重小于1的服务器不会被选中
        foreach ($indexWeight as $index => $weight) {
            if ($weight >= $threshold) {
                break;
            }
        }

        //尝试缓存链接
        if (!isset(self::$servers[$this->serverName][$index])) {
            if (!isset(self::$servers[$this->serverName])) {
                self::$servers[$this->serverName] = [];
            }
            $config                                   = Linker::Config()::get('servers')[$this->serverName][$index];
            self::$servers[$this->serverName][$index] = $this->newConnection($config, $index); //调用子类方法获得链接实例
        }

        return $index;
    }
}
