<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-07-06 15:16:52
 * @Modified time:      2018-10-23 13:24:48
 * @Depends on Linker:  None
 * @Description:        KV的调试输出
 */
namespace lin\basement\server\kv\structure;

use Linker;
use lin\basement\server\structure\BaseDebug;

class Debug extends BaseDebug
{
    protected $name           = 'S-KV';
    protected static $operate = []; //对不同的操作统计时间次数[type=>[counts, time],..]
    protected static $servers = []; //对不同的服务器统计[server1=>[counts, time],..]
    protected static $color   = ['get' => 'green', 'set' => 'orange', 'delete' => 'purple', 'flush' => 'red']; //操作使用的颜色

    public function handleSet($key, $value, $expire, $time, $index, $status)
    {
        $value = $this->formatValue($value);
        if ($expire == 0) {
            $expire = 'max life';
        } else if ($expire < 0) {
            $expire = '-1s'; //小于0直接设置-1
        } else {
            $expire = $expire . 's';
        }
        $info = "set $key; value: $value; <font style='color:rgb(150,150,150)'> --- [$expire life]</font>";
        $this->operate($info, $status, $time, 'set', $index);
    }

    public function handleGet($key, $value, $time, $index, $status)
    {
        if (!$status) {
            $value = null; //传过来原始值为false不是null
        }
        $value = $this->formatValue($value);
        $info  = "get $key; value: $value;";
        $this->operate($info, $status, $time, 'get', $index);
    }

    public function handleDelete($key, $time, $index, $status)
    {
        $info = "delete $key;";
        $this->operate($info, $status, $time, 'delete', $index);
    }

    public function handleFlush($time, $index, $status)
    {
        $info = 'flush;';
        $this->operate($info, $status, $time, 'flush', $index);
    }
    private function formatValue($value)
    {
        if (is_object($value)) {
            $value = 'object ( ' . get_class($value) . ' )';
        } else if (is_array($value)) {
            $value = ltrim(var_export($value, true), 'array (');
            $value = rtrim($value, ')');
            if (strlen($value) > 100) {
                $value = substr($value, 0, 100) . ' ... ';
            }
            $value = "array ($value)";
        } else if (is_string($value)) {
            if (strlen($value) > 100) {
                $value = substr($value, 0, 100) . '...';
            }
            $value = "'" . $value . "'";
        } else {
            $value = var_export($value, true);
        }

        return $value;
    }

}
