<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-12-15 10:10:29
 * @Modified time:      2018-11-02 13:55:40
 * @Depends on Linker:  Exception
 * @Description:        提供索引使用解析，连接接口和异常抛出
 */
namespace lin\basement\server\structure;

use Linker;

trait CommonTrait
{
    protected $Debug; //调试类
    protected $Driver; //驱动类
    protected $serverName; //当前服务器名
    protected $index; //当前使用的服务器索引
    protected static $servers = []; //缓存服务器驱动实例

    /**
     * 解析索引规则
     * @param  array  $servers  当前服务器配置文件
     * @param  string $rawIndex 使用的索引，如'index*,index2*'
     * @return array            [索引=>权重]
     */
    protected function parseRawIndex(array $servers, string $rawIndex): array
    {
        $indexArray = [];
        $rawIndex   = trim($rawIndex);
        if ($rawIndex === '*') {
            $indexArray = $servers; //全部键名
        } else {
            //解析索引使用规则
            $rawIndex = explode(',', $rawIndex);
            foreach ($rawIndex as $index) {
                $index = trim($index);
                if (preg_match('/\*/', $index)) {
                    $index = rtrim($index, '*');
                    foreach ($servers as $key => $nothing) {
                        if (preg_match("/^$index.*/", $key)) {
                            $indexArray[$key] = 1;
                        }
                    }
                } else {
                    $indexArray[$index] = 1;
                }
            }
        }

        //过滤不合法索引
        foreach ($indexArray as $index => $nothing) {
            if (!isset($servers[$index]) || $servers[$index]['weight'] <= 0) {
                unset($indexArray[$index]); //去掉不存在的和权重小于0的
            }
        }
        if (empty($indexArray)) {
            $this->exception('缺少可用服务器');
        }

        //计算累积权重
        $sum = 0;
        foreach ($indexArray as $index => $nothing) {
            $sum += ceil($servers[$index]['weight']);
            $indexArray[$index] = $sum;
        }
        return $indexArray;
    }
    protected function exception($info, $sub = '', $code = 1)
    {
        Linker::Exception()::throw ($info, $code, 'Server-' . $this->serverName, $sub);
    }

    public function getIndex():  ? string
    {
        return is_null($this->index) ? null : (string) $this->index;
    }
    public function getDriver() :  ? object
    {
        return $this->Driver ?: null;
    }
    public function getServerName():  ? string
    {
        return is_null($this->serverName) ? null : $this->serverName;
    }

    //建立新连接，若成功返回连接对象，失败返回null
    abstract protected function newConnection(array $config, $index) :  ? object;
}
