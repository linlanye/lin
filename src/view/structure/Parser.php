<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-06-20 11:53:48
 * @Modified time:      2018-11-02 13:43:55
 * @Depends on Linker:  Config Exception
 * @Description:        解析视图
 */
namespace lin\view\structure;

use Linker;
use lin\view\structure\Debug;

class Parser
{
    private $pattern = [
        'foreach(.+?)', //foreach标签
        '\/foreach',
        'if(.+?)', //if标签
        'else.*',
        'else\s*if(.+?)',
        '\/if',
        'while(.+?)', //while标签
        '\/while',
        'do', //do-while标签
        '\/while(.+?)',
        'switch(.+?)', //switch标签
        'case(.+?):?',
        'default\s*:?',
        '\/case',
        '\/default',
        '\/switch',
        ':\$?(\w+);?', //输出变量
        ':(.+?);?', //输出函数
        '(.+?);?', //任意语法,最后匹配
    ];
    private $replace = [
        '<?php foreach\\1{ ?>',
        '<?php } ?>',
        '<?php if\\1 { ?>',
        '<?php }else{ ?>',
        '<?php }else if\\1 { ?>',
        '<?php } ?>',
        '<?php while\\1 { ?>',
        '<?php } ?>',
        '<?php do{ ?>',
        '<?php }while\\1; ?>',
        '<?php switch\\1{ case \'_value_DY0zqeh1la2\':?>', //switch,比较特殊，少首行case则报错
        '<?php case \\1: ?>',
        '<?php default: ?>',
        '<?php break; ?>',
        '<?php break; ?>',
        '<?php } ?>',
        '<?php echo $\\1; ?>',
        '<?php echo \\1; ?>',
        '<?php \\1; ?>',
    ];

    private $tag = [
        'extends'  => '',
        'include'  => '',
        'static'   => '',
        'location' => '',
        'escape'   => '', //去掉转义
    ];
    private $config;
    private $data;
    private $status; //已初始化标记
    private $Debug;

    public function __construct()
    {
        $this->config                  = Linker::Config()::get('lin')['view']; //获取配置
        $this->config['cache']['path'] = rtrim($this->config['cache']['path'], '/') . '/';
        $this->config['path']          = rtrim($this->config['path'], '/') . '/';
        if ($this->config['debug']) {
            $this->Debug = new Debug;
        }
    }

    //输出视图
    public function show($view, $data)
    {
        if (is_callable($this->config['security'])) {
            $data = $this->handleSafe($this->config['security'], $data); //数据安全处理
        }
        $this->data = $data;

        $_cache_file_cnpa780DAH0xa = $this->handle($view);
        if ($this->data) {
            extract($this->data, EXTR_OVERWRITE);
        }
        header('content-type:text/html;charset=' . $this->config['charset']);
        include $_cache_file_cnpa780DAH0xa;
    }
    //获得解析后文件名
    public function getFile($view, $data)
    {
        if (is_callable($this->config['security'])) {
            $data = $this->handleSafe($this->config['security'], $data); //数据安全处理
        }
        $this->data = $data;

        $file = $this->handle($view);
        return $file;
    }

    //显示视图
    private function handle($view)
    {
        $path       = $this->config['cache']['path'];
        $cache_file = $path . md5($view) . '.html'; //实际打开的文件名
        $life       = $this->config['cache']['life'];

        //缓存过期
        if (!file_exists($cache_file) || $life < 0 || ($life && time() > filemtime($cache_file) + $life)) {
            if (!file_exists($path) && !mkdir($path, 0750, true)) {
                $this->exception('目录创建失败', $path);
            }
            $content = $this->parse($view);
            $content = preg_replace($this->tag['escape'], '', $content); //去掉转义符，不可在parse方法中进行，防止递归解析出错
            file_put_contents($cache_file, $content);
        }

        if ($this->Debug) {
            $this->Debug->show($cache_file);
        }

        return $cache_file;
    }

    //解析视图文件并获得内容
    private function parse($view)
    {
        $t    = microtime(true);
        $file = $this->config['path'] . $view . '.php';
        if (!file_exists($file)) {
            $this->exception('文件不存在', $file); //确保视图文件存在
        }

        //初始化标签计算
        if (!$this->status) {
            $this->init();
            $this->status = 1;
        }
        $content = file_get_contents($file);

        //1,处理继承标签,r中为[匹配到的整个继承标签，模版名],只继承一个
        if (preg_match($this->tag['extends'], $content, $r)) {
            $content = str_replace($r[0], '', $content); //去除继承标签
            $parent  = $this->parse($r[1]); //获得标签内容
            $parent  = preg_split($this->tag['location'], $parent); //定位父模板的继承点
            $n       = count($parent);
            if ($n < 2) {
                $content = $parent[0] . $content; //若无定位点，则等同于将内容至于继承模版之后
            } else {
                $_content = '';
                for ($i = 0; $i < $n - 1; $i++) {
                    $_content .= $parent[$i] . $content; //将内容插入多个定位点
                }
                $content = $_content . end($parent); //补上最后一个定位点，
            }
        }

        //2,处理include标签
        if (preg_match_all($this->tag['include'], $content, $r, PREG_SET_ORDER)) {
            foreach ($r as $v) {
                $content = explode($v[0], $content, 2); //分割原内容为两部分
                $include = $this->parse($v[1]); //获得标签内容
                $content = $content[0] . $include . $content[1];
            }
        }

        //3.处理常规标签
        $content = $this->parseGeneral($content);

        //4.找出需要静态化的区域
        if (preg_match($this->tag['static'], $content)) {
            $tmp = tempnam($this->config['cache']['path'], 'L');
            file_put_contents($tmp, $content); //暂时缓存
            $static_content = $this->getOBContents($tmp);
            unlink($tmp);
            if (preg_match_all($this->tag['static'], $static_content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $key => $v) {
                    $content = preg_replace($this->tag['static'], $v[1], $content, 1); //逐次替换静态内容
                }
            }
        }

        if ($this->Debug) {
            $this->Debug->parse($file, microtime(true) - $t);
        }

        return $content;
    }

    //解析常规标签
    private function parseGeneral($content)
    {
        $content = preg_replace($this->pattern, $this->replace, $content);

        //尝试将静态标签和定位标签复原
        $left                = preg_quote('<?php ');
        $right               = preg_quote('; ?>');
        $target_static_begin = $left . preg_quote($this->config['tag']['begin_static'], '/') . $right;
        $target_static_end   = $left . preg_quote($this->config['tag']['end_static'], '/') . $right;
        $target_location     = $left . preg_quote($this->config['tag']['location'], '/') . $right;

        $left                = $this->config['tag']['left'];
        $right               = $this->config['tag']['right'];
        $source_static_begin = $left . $this->config['tag']['begin_static'] . $right;
        $source_static_end   = $left . $this->config['tag']['end_static'] . $right;
        $source_location     = $left . $this->config['tag']['location'] . $right;

        $content = preg_replace("/$target_static_begin/", $source_static_begin, $content);
        $content = preg_replace("/$target_static_end/", $source_static_end, $content);
        $content = preg_replace("/$target_location/", $source_location, $content);
        return $content;
    }

    //初始化标签
    private function init()
    {
        //转义限定符
        $left   = preg_quote($this->config['tag']['left'], '/');
        $right  = preg_quote($this->config['tag']['right'], '/');
        $escape = preg_quote($this->config['tag']['escape'], '/');

        $location_tag = preg_quote($this->config['tag']['location'], '/');
        $extends_tag  = preg_quote(trim($this->config['tag']['extends']), '/') . '\s+([\w\/]+?)';
        $include_tag  = preg_quote(trim($this->config['tag']['include']), '/') . '\s+([\w\/]+?)'; //去掉可能的空格

        $begin_static        = $left . preg_quote($this->config['tag']['begin_static'], '/') . $right;
        $end_static          = $left . preg_quote($this->config['tag']['end_static'], '/') . $right;
        $this->tag['escape'] = "/$escape(?=${left})/"; //去掉转义字符
        $escape              = "(?<!$escape)"; //断言规则,用于不匹配转义符

        //生成最终pattern
        foreach ($this->pattern as &$v) {
            $v = '/' . $escape . $left . $v . $right . '/';
        }
        $this->tag['location'] = '/' . $escape . $left . $location_tag . $right . '/';
        $this->tag['extends']  = '/' . $escape . $left . $extends_tag . $right . '/';
        $this->tag['include']  = '/' . $escape . $left . $include_tag . $right . '/';
        $this->tag['static']   = '/' . $escape . $begin_static . '([\s\S]*?)' . $escape . $end_static . '/'; //匹配任意字符包括换行符
    }
    //获得缓存内容，静态化用
    private function getOBContents($_tmp_Y0DNLAS7023)
    {
        ob_start(); //打开输出缓冲区
        if ($this->data) {
            extract($this->data, EXTR_OVERWRITE); //抽取变量
        }
        include $_tmp_Y0DNLAS7023;
        return ob_get_clean(); //获得缓冲区内容
    }

    //数据安全化
    private function handleSafe($security, $data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = $this->handleSafe($security, $v);
            } else if (is_scalar($v)) {
                $data[$k] = call_user_func($security, $v);
            }
        }
        return $data;
    }
    private function exception($info, $subInfo = '')
    {
        Linker::Exception()::throw ($info, 1, 'View', $subInfo);
    }
}
