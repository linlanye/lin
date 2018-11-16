<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-11-28 15:54:10
 * @Modified time:      2018-08-29 16:58:23
 * @Depends on Linker:  ServerQueue
 * @Description:        将日志放入队列
 */
namespace lin\basement\log\structure;

use Linker;

class QueueHandler
{
    public function write($raw, $config)
    {
        $Driver = Linker::ServerQueue(true);

        //处理成批量插入的数据
        $data = [];
        foreach ($raw as $item) {
            $logName = $item[0];
            unset($item[0]);
            if (!isset($data[$logName])) {
                $data[$logName] = [];
            }
            $data[$logName][] = call_user_func_array($config['format'], $item);
        }
        foreach ($data as $logName => $value) {
            $Driver->setName($config['prefix'] . $logName);
            $Driver->multiPush($value);
        }
        return true;
    }
}
