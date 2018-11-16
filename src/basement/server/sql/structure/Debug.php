<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-07-02 14:13:45
 * @Modified time:      2018-09-29 16:17:29
 * @Depends on Linker:  Debug
 * @Description:        采集输送debug信息
 */
namespace lin\basement\server\sql\structure;

use Linker;
use lin\basement\server\structure\BaseDebug;

class Debug extends BaseDebug
{
    protected $name           = 'S-SQL';
    protected static $operate = []; //对不同的操作统计时间次数[type=>[counts, time],..]
    protected static $servers = []; //对不同的服务器统计[server1=>[counts, time],..]
    protected static $color   = ['read' => 'green', 'write' => 'orange']; //操作使用的颜色

    public function execute($sql, $time, $serverIndex, $status, $operate = null, $params = null)
    {
        //生成用于记录执行过的sql
        if ($params) {
            if (is_numeric(key($params))) {
                //?占位符，一次只能替换一个
                foreach ($params as $key => $value) {
                    $sql = preg_replace('#[\?]#', "'$value'", $sql, 1);
                }
            } else {
                //替换绑定变量名为真实值，一次可能对应多个值
                $pattern = [];
                $replace = [];
                foreach ($params as $key => $value) {
                    $replace[] = "'$value'";
                    $pattern[] = "#$key(?!\w)#";
                }
                $sql = preg_replace($pattern, $replace, $sql);
            }
        }
        $sql .= '; ';
        if (is_int($status)) {
            $sql .= "<font style='color:rgb(150,150,150)'> --- [$status rows]</font>"; //记录影响的记录数
        }

        $this->operate($sql, $status, $time, $operate, $serverIndex);
    }

}
