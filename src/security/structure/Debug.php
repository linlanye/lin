<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-09-27 20:15:56
 * @Modified time:      2018-11-05 16:35:12
 * @Depends on Linker:  Debug
 * @Description:        收集调试信息
 */
namespace lin\security\structure;

use Linker;
use lin\security\Security;

class Debug
{
    private $Debug;
    private static $time   = ['b' => 0, 'c' => 0];
    private static $counts = ['b' => 0, 'c' => 0];

    public function __construct()
    {
        $this->Debug = Linker::Debug(true);
        $this->Debug->setName('SECURITY');
    }

    public function build($id, $scenario, $token, $life, $t)
    {
        $this->statistics($t, 'b');

        if ($id === null) {
            $info = 'client: temporary; ';
        } else {
            $info = "client: $id; ";
        }
        $info .= "scenario: $scenario, ${life}s; token: $token" . $this->getSubInfo($t);

        $this->Debug->append('创建明细', $info);
    }

    public function check($id, $scenario, $code, $t)
    {
        $this->statistics($t, 'c');

        if ($id === null) {
            $info = 'client: temporary; ';
        } else {
            $info = "client: $id; ";
        }
        $info .= "scenario: $scenario";
        $s      = "<font style='color:red'>";
        $status = '';
        switch ($code) {
            case SECURITY::VALID:
                $status = '; success';
                break;
            case SECURITY::UNCHECKED:
                $status .= "; ${s}failed, unchecked</font>";
                break;
            case SECURITY::NONE:
                $status .= "; ${s}failed, scenario none</font>";
                break;
            case SECURITY::FAILED:
                $status .= "; ${s}failed, token error</font>";
                break;
            case SECURITY::EXPIRED:
                $status .= "; ${s}failed, token expired</font>";
                break;
        }
        $info .= $this->getSubInfo($t, $status);
        $this->Debug->append('执行明细', $info);
    }

    //统计
    private function statistics($t, $type)
    {
        ++self::$counts[$type];
        self::$time[$type] += $t;

        $u = '<font style="color:#646464">';

        $data = [
            '创建数' => self::$counts['b'] . $this->getSubInfo(self::$time['b']),
            '执行数' => self::$counts['c'] . $this->getSubInfo(self::$time['c']),
        ];

        $this->Debug->set('统计', $data);
    }

    private function getSubInfo($t, $info = '')
    {
        $t = round($t * 1000, 2);
        return "; <font style='color:#646464'>(${t}ms$info)";
    }
}
