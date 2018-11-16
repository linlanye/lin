<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-07-16 19:18:45
 * @Modified time:      2018-09-29 16:19:54
 * @Depends on Linker:  Debug
 * @Description:        采集输送debug信息
 */
namespace lin\basement\server\local\structure;

use Linker;
use lin\basement\server\structure\BaseDebug;

class Debug extends BaseDebug
{
    protected $name           = 'S-LOCAL';
    protected static $operate = []; //对不同的操作统计时间次数[type=>[counts, time],..]
    protected static $servers = []; //对不同的服务器统计[server1=>[counts, time],..]
    protected static $color   = ['read' => 'green', 'write' => 'orange']; //操作使用的颜色

    public function read($file, $mode, $status, $t)
    {
        $info = "read $file; <font style='color:rgb(150,150,150)'> --- [$mode mode]</font>";
        $this->operate($info, $status, $t, 'read', 'local');
    }
    public function write($file, $mode, $status, $t)
    {
        $info = "write $file; <font style='color:rgb(150,150,150)'> --- [$mode mode]</font>";
        $this->operate($info, $status, $t, 'write', 'local');
    }

}
