<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-10-17 15:07:30
 * @Modified time:      2018-11-08 10:05:01
 * @Depends on Linker:  Config Lang Request
 * @Description:        调试类
 */
namespace lin\basement\debug;

use Linker;

class Debug
{
    /*****basement*****/
    use \basement\Debug;

    public function setName(string $name): bool
    {
        $this->__name = $name;
        return true;
    }

    //追加信息
    public function append(string $key, $data): bool
    {
        if (!isset(self::$data[$this->__name])) {
            self::$data[$this->__name] = [];
        }
        if (isset(self::$data[$this->__name][$key])) {
            if (!is_array(self::$data[$this->__name][$key])) {
                self::$data[$this->__name][$key] = [self::$data[$this->__name][$key]];
            }
            array_push(self::$data[$this->__name][$key], $data);
        } else {
            self::$data[$this->__name][$key] = [$data];
        }
        return true;
    }

    public function get(string $key)
    {
        return self::$data[$this->__name][$key] ?? null;
    }

    public function set(string $key, $data): bool
    {
        if (!isset(self::$data[$this->__name])) {
            self::$data[$this->__name] = [];
        }
        self::$data[$this->__name][$key] = $data; //直接赋值覆盖
        return true;
    }

    //设置所有信息
    public function setAll(array $data): bool
    {
        self::$data[$this->__name] = $data;
        return true;
    }
    //获取所有信息
    public function getAll():  ? array
    {
        return self::$data[$this->__name] ?? null;
    }

    public static function dump($arg, ...$moreArgs) : bool
    {
        ob_start();
        var_dump($arg);
        if (!empty($moreArgs)) {
            foreach ($moreArgs as $value) {
                var_dump($value);
            }
        }
        $content = ob_get_clean();

        $content = preg_replace('#=>\n[\s]+#', '=>', $content);
        $content = preg_replace('#]=>(.+\([0-9]+\)?)#', ']=> <small><i>\\1</i></small>', $content);
        echo '<div><pre>' . $content . '</pre></div>';
        return true;
    }

    /**
     * 设立标识，收集标识内的系统信息，位于begin和end之间
     * @param  string $flagName 标识名
     * @return bool
     */
    public static function beginFlag(string $flagName = 'default'): bool
    {
        self::$flag[$flagName] = [memory_get_usage(), microtime(true)]; //注，内存的统计由于代码所处上下文不同会导致统计不准
        return true;
    }
    public static function endFlag(string $flagName = 'default'): bool
    {
        $data = self::$flag[$flagName] ?? [];
        if (isset($data[2])) {
            return false; //标示已结束过
        }
        $time                  = microtime(true);
        $mem                   = memory_get_usage();
        $mem                   = $mem - $data[0];
        $time                  = $time - $data[1];
        self::$flag[$flagName] = [$mem, $time, 1]; //存储消耗的内存和结束标志
        return true;
    }

    /**
     * 获得目标标识的信息
     * @param  string $flag 标识名
     * @return mixed|null   返回某个标识中的内容，失败时返回null
     */
    public static function getFlag(string $flagName = 'default')
    {
        return self::$flag[$flagName] ?? null;
    }
    /******************/

    private static $status = 0;
    private static $flag   = []; //存放标识之间的系统信息
    private static $data   = [];

    public function __construct(string $name = '')
    {
        $config       = Linker::Config()::get('lin')['debug'];
        $this->__name = $name ?: $config['default']['name'];
    }

    public static function run(): void
    {
        if (!self::$status) {
            register_shutdown_function(['\\lin\\basement\\debug\\Debug', '_shutdown_output_zhP7d213ohd']);
            self::$status = 1;
        }
    }
    public static function _shutdown_output_zhP7d213ohd()
    {
        $config = Linker::Config()::get('lin')['debug'];
        //确保js代码不会出错
        if (!is_scalar($config['panel']['display'])) {
            $config['panel']['display'] = 'none';
        }
        if (!is_scalar($config['panel']['name']['prior'])) {
            $config['panel']['name']['prior'] = 'SYSTEM';
        }

        $data = self::getAllInfo($config['lang']); //获得所有debug信息
        ksort($data);

        //需要显示
        if ($config['panel']['display'] && $config['panel']['display'] != 'none') {
            foreach ($config['panel']['name']['hidden'] as $key => $name) {
                if (isset($data[$name])) {
                    unset($data[$name]); //释放隐藏的数据
                }
            }
            $columns = array_keys($data); //获取标识名，认为每一个标识名为一列
            $data    = json_encode($data);
            include __DIR__ . '/structure/debug.html';
        }
    }

    //清除收集的数据
    public static function clean(string $name = ''): bool
    {
        if ($name) {
            self::$data = [];
        } else {
            unset(self::$data[$name]);
        }
        self::$flag = [];
        return true;
    }
    //重置
    public static function reset(): bool
    {
        self::$status = 0;
        self::$data   = [];
        self::$flag   = [];
        return true;
    }

    //自动设立开头结尾标识
    public static function flag(string $flagName): bool
    {
        $flagName = 'flag ' . $flagName;
        if (isset(self::$flag[$flagName])) {
            self::endFlag($flagName);
        } else {
            self::beginFlag($flagName);
        }
        return true;
    }

    private static function getAllInfo($lang_mode)
    {
        $font0      = '<font style="color:rgb(100,100,100)">';
        $font1      = '</font>';
        $data       = [];
        $start_time = $_SERVER['REQUEST_TIME_FLOAT'];
        $time       = microtime(true) - $start_time + 0.00000001; //time可能为0
        $mem        = memory_get_usage(); //总用量

        //处理flag信息
        if (!empty(self::$flag)) {
            $flagData = [];
            foreach (self::$flag as $key => $value) {
                if (!isset($value[2])) {
                    continue; //未结束标示的不予输出
                }
                $_mem   = round($value[0] / 1024, 2);
                $r_mem  = round($value[0] / $mem * 100, 2);
                $_time  = round(($value[1] * 1000), 2); //转化为毫秒
                $r_time = round(($value[1] / $time) * 100, 2);
                $value  = "time: $r_time%$font0(${_time}ms)$font1; ";
                if ($_mem > 0) {
                    $value .= "memory: $r_mem%$font0(${_mem}Kb)$font1";
                } else {
                    $value .= "memory: unknown";
                }
                $flagData[$key] = $value;
                unset(self::$flag[$key]); //清空已结束的标示
            }
            !empty($flagData) && self::$data['FLAG'] = $flagData; //输出flag数据
        }

        //计算系统参数
        $peak_memory                    = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $data['SYSTEM']['总耗时']    = (round($time * 1000, 2)) . "${font0}ms$font1"; //得到运行时间 毫秒
        $data['SYSTEM']['吞吐率']    = round((1 / $time), 2) . "{$font0}req/s$font1";
        $data['SYSTEM']['内存用量'] = round($mem / 1024 / 1024, 2) . "~$peak_memory{$font0}Mb$font1";

        $data['SYSTEM']['操作系统']    = $_SERVER['OS'] ?? 'unknown';
        $data['SYSTEM']['服务器软件'] = $_SERVER['SERVER_SOFTWARE'] ?? 'none';
        $data['SYSTEM']['临时文件夹'] = $_SERVER['TEMP'] ?? 'unknown';
        $data['SYSTEM']['文件数']       = count(get_included_files());
        $data['SYSTEM']['文件明细']    = get_included_files();

        //获得http参数
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $params = strtoupper($_SERVER['REQUEST_METHOD']) === 'GET' ? $_GET : $_POST;
            $params = $params ?: null;
        } else {
            $params = null;
        }

        $data['HTTP']['Request'] = [
            'path'       => Linker::Request()::getURL(),
            'method'     => $_SERVER['REQUEST_METHOD'] ?? 'CONSOLE',
            'parameters' => var_export($params, true),
            'server'     => 'ip: ' . ($_SERVER['SERVER_ADDR'] ?? 'unknown') . '; port: ' . ($_SERVER["SERVER_PORT"] ?? 'unknown') . '; domain: ' . ($_SERVER['HTTP_HOST'] ?? 'unknown'),
            'client'     => 'ip: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '; port: ' . ($_SERVER["REMOTE_PORT"] ?? 'unknown'),
        ];
        $data['HTTP']['Status']     = $_SERVER['REDIRECT_STATUS'] ?? 'NULL';
        $data['HTTP']['Referer']    = $_SERVER['HTTP_REFERER'] ?? 'NULL';
        $data['HTTP']['Cookie']     = $_SERVER['HTTP_COOKIE'] ?? 'NULL';
        $data['HTTP']['Protocol']   = $_SERVER['SERVER_PROTOCOL'] ?? 'CONSOLE';
        $data['HTTP']['User Agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'CONSOLE';

        $data['HTTP']['GLOBALS'] = [];
        if ($_SESSION) {
            $data['HTTP']['GLOBALS']['$_SESSION'] = var_export($_SESSION, true);
        }
        if ($_GET) {
            $data['HTTP']['GLOBALS']['$_GET'] = var_export($_GET, true);
        }
        if ($_POST) {
            $data['HTTP']['GLOBALS']['$_POST'] = var_export($_POST, true);
        }
        if ($_COOKIE) {
            $data['HTTP']['GLOBALS']['$_COOKIE'] = var_export($_COOKIE, true);
        }
        if ($_FILES) {
            $data['HTTP']['GLOBALS']['$_FILES'] = var_export($_FILES, true);
        }
        if ($_ENV) {
            $data['HTTP']['GLOBALS']['$_ENV'] = var_export($_ENV, true);
        }
        if (!$data['HTTP']['GLOBALS']) {
            unset($data['HTTP']['GLOBALS']);
        }

        //处理多语言
        if (!$lang_mode || $lang_mode == 'none') {
            self::$data = array_merge($data, self::$data); //用户信息优先(HTTP,SYSTEM可能被占用)
            return self::$data;
        }

        $Lang = Linker::Lang(true); //需要多元数据进行翻译
        $Lang->setLabel('lin');

        if ($lang_mode == 'both') {
            $data['SYSTEM'] = self::getLang($data['SYSTEM'], $Lang); //翻译系统信息
            self::$data     = self::getLang(self::$data, $Lang);
            self::$data     = array_merge($data, self::$data); //数据和显示一致
            return self::$data;
        }

        self::$data = array_merge($data, self::$data); //数据和显示不一致
        return self::getLang(self::$data, $Lang);
    }

    //对数据中的key值进行多语言映射
    private static function getLang($data, $Lang)
    {
        if (is_array($data)) {
            $output = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = self::getLang($value, $Lang); //寻找可以映射的key
                }
                if (is_string($key)) {
                    $key = $Lang->map($key); //只对key进行映射
                }
                $output[$key] = $value;
            }
            return $output;
        }
        if (is_string($data)) {
            return $Lang->map($data); //只对字符串进行多语言映射
        }
        return $data;
    }
}
