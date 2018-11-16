<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-12-05 08:27:41
 * @Modified time:      2018-09-29 16:18:04
 * @Depends on Linker:  none
 * @Description:
 */

namespace lin\basement\server\queue\structure;

use lin\basement\server\structure\BaseDebug;

class Debug extends BaseDebug
{
    protected $name           = 'S-QUEUE';
    protected static $operate = []; //对不同的操作统计时间次数[type=>[counts, time],..]
    protected static $servers = []; //对不同的服务器统计[server1=>[counts, time],..]
    protected static $color   = ['pop' => 'green', 'push' => 'orange']; //操作使用的颜色
    public function pop($name, $amount, $time, $serverIndex, $status)
    {
        $info = "pop $name;";
        if ($amount > 1) {
            $info .= "<font style='color:rgb(150,150,150)'> --- [$amount times]</font>";
        }
        $this->operate($info, $status, $time, 'pop', $serverIndex);
    }
    public function push($name, $amount, $time, $serverIndex, $status)
    {
        $info = "push $name;";
        if ($amount > 1) {
            $info .= "<font style='color:rgb(150,150,150)'> --- [$amount times]</font>";
        }
        $this->operate($info, $status, $time, 'push', $serverIndex);
    }

}
