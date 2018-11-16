<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-19 17:16:10
 * @Modified time:      2018-10-23 13:31:58
 * @Depends on Linker:  Config
 * @Description:        使用Redis类实现队列，采用hash方式，跟据队列名散列到不同的服务器
 */
namespace lin\basement\server\queue;

use Linker;
use lin\basement\server\queue\structure\Debug;
use lin\basement\server\structure\HashServer;
use Redis;
use Throwable;

class QueueRedis extends HashServer
{
    /*****basement*****/
    use \basement\ServerQueue;
    public function setName($queueName): bool
    {
        $this->chooseDriver($queueName);
        $this->__name = $queueName;
        return true;
    }

    public function push($data): bool
    {
        $t = microtime(true);
        $r = $this->Driver->rPush($this->__name, serialize($data));
        if ($this->Debug) {
            $this->Debug->push($this->__name, 1, microtime(true) - $t, $this->index, $r);
        }
        return (bool) $r;
    }
    public function multiPush(array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        $t = microtime(true);
        $this->Driver->multi(Redis::MULTI); //采用事务
        foreach ($data as $v) {
            $this->Driver->rPush($this->__name, serialize($v));
        }
        $r      = $this->Driver->exec()[0] ?? false; //选择第一个结果即可
        $amount = count($data);

        if ($this->Debug) {
            $this->Debug->push($this->__name, $amount, microtime(true) - $t, $this->index, $r);
        }
        return (bool) $r;
    }

    /**
     * 弹出数据
     * @param   integer $amount  每次弹出的数量
     * @return  array   array($data1,...$datan) 成功则以数组形式返回，键值为数据，失败或无数据则返回null
     */
    public function pop(int $amount = 1):  ? array
    {
        $t = microtime(true);
        if ($amount > 1) {
            $this->Driver->multi(Redis::MULTI); //采用事务
            $this->Driver->lRange($this->__name, 0, $amount - 1);
            $this->Driver->lTrim($this->__name, $amount, -1);
            $data = $this->Driver->exec()[0]; //只取第一个操作的结果
            if (empty($data)) {
                $data = false;
            } else {
                foreach ($data as $key => $value) {
                    $data[$key] = unserialize($value);
                }
            }
        } else {
            $data = $this->Driver->lPop($this->__name);
            if ($data !== false) {
                $data = [unserialize($data)];
            }
        }
        if ($this->Debug) {
            $this->Debug->pop($this->__name, $amount, microtime(true) - $t, $this->index, $data !== false);
        }
        return $data === false ? null : $data;
    }
    //队列是否为空
    public function isEmpty() : bool
    {
        return (bool) !$this->Driver->lSize($this->__name);
    }
    //获得队列大小
    public function getSize(): int
    {
        $size = $this->Driver->lSize($this->__name);
        if ($size === false) {
            return 0;
        }
        return $size;
    }

    /******************/

    protected static $servers;
    protected static $caches;

    public function __construct($DriverOrIndex = null)
    {
        $config       = Linker::Config()::get('lin')['server']['queue'];
        $this->Debug  = $config['debug'] ? new Debug : null;
        $this->__name = $config['default']['name'];
        $this->init('redis', $DriverOrIndex, $config['driver']['redis']['use']);
        $this->chooseDriver($this->__name);
    }

    //根据使用的服务器索引获得驱动
    protected function newConnection(array $config, $index):  ? object
    {
        $Redis = new Redis();
        try {
            $r = $Redis->connect($config['host'], $config['port'], $config['timeout']);
        } catch (Throwable $e) {
            $r = false; //抑制其错误或异常
        }
        if ($r && $config['pwd']) {
            $r = $Redis->auth($config['pwd']);
        }
        if (!$r) {
            $this->exception('服务器连接失败', $index); //队列为可靠数据服务，连接出错需抛出异常
        }
        return $Redis;
    }
}
