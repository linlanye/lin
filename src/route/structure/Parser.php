<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-11-01 15:40:25
 * @Modified time:      2018-11-02 13:50:43
 * @Depends on Linker:  Exception
 * @Description:        路由解析器
 */
namespace lin\route\structure;

use Closure;
use Linker;
use lin\route\structure\ClosureToString;
use lin\route\structure\Debug;

class Parser
{
    private $config;
    private $Debug;
    private $ParseClosure; //将闭包输出成字符串的类
    public function __construct($config)
    {
        $this->config = $config;
        if ($this->config['debug']) {
            $this->Debug = new Debug;
        }
    }

    /**
     * 执行路由解析
     * @param  string $files  需要执行的用户路由文件
     * @param  string $url    当前请求的url
     * @param  string $method 当前请求的方法
     * @return array          执行规则，类似['rules'=>[非空],'params'=>[可空]]
     */
    public function execute($files, $url, $method)
    {
        //读取用户规则
        $t          = microtime(true);
        $cache_file = rtrim($this->config['cache']['path'], '/') . '/' . md5($files); //缓存文件名
        $cache_on   = $this->config['cache']['on']; //缓存是否开启

        //非缓存模式或缓存文件不存在，直接读取用户路由文件
        if (!$cache_on || !file_exists($cache_file . "$method.php")) {
            $files = $this->loadFiles($files); //读取路由文件，并获得所读取的具体文件
            $rules = Creator::getRules(); //从构建器获得规则
            if ($cache_on && $rules) {
                $this->setCache($cache_file, $rules); //规则非空写缓存
            }
        } else {
            $file  = $cache_file . "$method.php";
            $rules = [$method => include $file]; //读缓存
            $files = [$file]; //缓存文件即为路由文件，用于debug
        }
        //不存在规则
        if (empty($rules[$method])) {
            $r = null;
        } else {
            $r = $this->match($rules[$method], $url); //匹配结果
        }
        if ($this->Debug) {
            $this->Debug->parse($files, $rules, microtime(true) - $t); //记录运行时间，加载文件数，规则数
        }

        return $r;
    }

    /**
     * 从当前路由规则中匹配规则
     * @param  array  $rules 当前请求方法的路由规则
     * @param  string $url   当前请求url
     * @return array|null    匹配结果
     */
    private function match($rules, $url)
    {
        //大小写不敏感匹配
        if ($this->config['ci']) {
            $url        = strtolower($url);
            $rules['S'] = array_change_key_case($rules['S'], CASE_LOWER);
            $rules['D'] = array_change_key_case($rules['D'], CASE_LOWER);
        }

        if (isset($rules['S'][$url])) {
            $rule   = $rules['S'][$url]; //匹配静态规则
            $params = [];
            $type   = 'static'; //debug用
        } else {
            $match = false; //匹配动态规则
            foreach ($rules['D'] as $pattern => $rule) {
                if (preg_match_all($pattern, $url, $params, PREG_SET_ORDER)) {
                    $match = true;
                    break; //匹配动态规则
                }
            }
            if (!$match) {
                return null; //未匹配到规则
            }
            $params = $params[0];
            unset($params[0]);
            $params = array_combine($rule['params'], $params); //处理动态参数
            $type   = 'dynamic';
        }

        if (isset($rule['pre'])) {
            $rule['main'] = array_merge($rules['PRE'][$rule['pre']], $rule['main']); //存在前置执行
        }
        if (isset($rule['post'])) {
            $rule['main'] = array_merge($rule['main'], $rules['POST'][$rule['post']]); //存在后置执行
        }
        if ($this->Debug) {
            $this->Debug->type($type);
        }
        return ['rules' => $rule['main'], 'params' => $params];
    }

    /**
     * 写规则缓存
     * @param string $file  缓存文件名前缀
     * @param array $rules  所有的路由规则
     */
    private function setCache($file, $rules)
    {
        $dir = dirname($file);
        if (!file_exists($dir) && !mkdir($dir, 0750, true)) {
            $this->exception('目录创建失败', $dir);
        }
        foreach ($rules as $method => $rule) {
            file_put_contents($file . "$method.php", '<?php return ' . $this->varExport($rule) . ';'); //输出缓存
        }
    }

    /**
     * 加载由Route定义的规则
     * @param  string $files 用户指定的路由文件匹配样式
     * @return array         实际加载的路由文件
     */
    private function loadFiles($files)
    {
        //生成匹配文件样式
        $path  = rtrim($this->config['path'], '/') . '/';
        $files = explode(',', $files);
        foreach ($files as &$file) {
            $file = trim($file);
            $end  = substr($file, -1);
            if ($end == '/') {
                $file .= '*'; //末尾为目录符号，加入通配符
            } else if ($end != '*') {
                $file .= '.php'; //末尾非通配符，加入后缀
            }
            $file = $path . ltrim($file, '/'); //生成完整的文件名
        }

        //获得目标规则文件
        $tmp_files = $files; //debug用
        $files     = $this->scanDir($files);
        if (!$files) {
            $this->exception('文件不存在', implode(', ', $tmp_files));
        }

        //加载规则文件
        foreach ($files as $file) {
            include $file;
        }
        return $files;
    }

    /**
     * 递归获得目录下所有文件，满足一般shell匹配规则
     * @param  array $files 文件或目录样式
     * @return array        解析出来的文件
     */
    private function scanDir($files)
    {
        $final_files = [];
        do {
            $current = glob(array_pop($files));
            foreach ($current as $file) {
                if (is_dir($file)) {
                    array_push($files, rtrim($file, '/') . '/*'); //属于目录则压栈递归
                } else {
                    $final_files[] = $file;
                }
            }
        } while ($files);
        return array_unique($final_files); //去重复
    }

    //用于生成缓存字符串的方法
    private function varExport($rule)
    {
        $str = '';
        if ($rule instanceof Closure) {
            if (!$this->ParseClosure) {
                $this->ParseClosure = new ClosureToString;
            }
            return $this->ParseClosure->getString($rule); //转化闭包为字符串
        }

        if (is_array($rule)) {
            $str .= '[';
            if (array_keys($rule) === range(0, count($rule) - 1)) {
                foreach ($rule as $v) {
                    $str .= $this->varExport($v) . ','; //索引数组
                }
            } else {
                foreach ($rule as $key => $v) {
                    $str .= "'" . $key . "'=>" . $this->varExport($v) . ',';
                }
            }
            $str = rtrim($str, ',');
            $str .= ']';
        } else {
            $str .= var_export($rule, true);
        }
        return $str;
    }

    private function exception($info, $subinfo = '')
    {
        Linker::Exception()::throw ($info, 1, 'Route', $subinfo);
    }
}
