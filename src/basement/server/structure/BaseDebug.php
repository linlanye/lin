<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-12-13 12:44:08
 * @Modified time:      2018-11-05 16:47:42
 * @Depends on Linker:  Debug
 * @Description:        输出外部服务器相关调试信息
 */
namespace lin\basement\server\structure;

use Linker;

class BaseDebug
{
    protected $Debug;
    protected $name;
    protected static $operate = []; //对不同的操作统计时间次数[type=>[counts, time],..]
    protected static $servers = []; //对不同的服务器统计[server1=>[counts, time],..]
    protected static $color   = []; //操作使用的颜色

    public function __construct()
    {
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName($this->name);
    }
    protected function statistics()
    {
        $total_time   = 0;
        $total_counts = 0;
        $operates     = [];
        foreach (static::$operate as $operate => $item) {
            $color              = static::$color[$operate];
            $operate            = "operator <font style='color:$color'>$operate</font>";
            $operates[$operate] = $item[0] . ';' . $this->getSubInfo($item[1]);

            $total_counts += $item[0];
            $total_time += $item[1];

        }
        $servers = [];
        foreach (static::$servers as $server => $item) {
            $server           = "server $server";
            $servers[$server] = $item[0] . ';' . $this->getSubInfo($item[1]);
        }
        $data = [
            '执行数' => $total_counts . ';' . $this->getSubInfo($total_time),
        ];
        $data = array_merge($data, $servers);
        $data = array_merge($data, $operates);
        $this->Debug->set('统计', $data);
    }

    /**
     * 调用明细
     * @param  string $info    操作信息
     * @param  bool   $status  操作状态
     * @param  float  $time    操作时间
     * @param  string $operate 操作名
     * @param  string $server  服务器索引名
     * @return void
     */
    protected function operate($info, $status, $time, $operate, $server)
    {
        if (!isset(static::$operate[$operate])) {
            static::$operate[$operate] = [0, 0];
        }
        if (!isset(static::$servers[$server])) {
            static::$servers[$server] = [0, 0];
        }

        static::$operate[$operate][0]++;
        static::$operate[$operate][1] += $time;

        static::$servers[$server][0]++;
        static::$servers[$server][1] += $time;

        $this->statistics();
        if ($status) {
            $status = '; success';
        } else {
            $status = '; <font style="color:red">failed</font>';
        }
        $this->Debug->append('执行明细', $info . $this->getSubInfo($time, $status));
    }

    protected function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return " <font style='color:#646464'>(${t}ms$info)";
    }
}
