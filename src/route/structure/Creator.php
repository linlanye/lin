<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-01-13 23:30:47
 * @Modified time:      2018-11-02 13:49:28
 * @Depends on Linker:  Config Exception
 * @Description:        路由规则创建器，规则有三种格式，
 *                      ['url'=>'class.method']、['url'=>['class.method','class2.method2']]、['url'=>Closure]
 */
namespace lin\route\structure;

use Closure;
use Linker;

class Creator
{
    private static $rules   = []; //生成的规则
    private static $counter = ['pre' => 0, 'post' => 0]; //使用计数器表示前置和后置id
    private $namespace;
    private $pre  = []; //前置执行
    private $post = []; //后置执行

    public function __construct()
    {
        $namespaces = Linker::Config()::get('lin')['route']['namespace'];
        foreach ($namespaces as &$namespace) {
            $namespace = rtrim($namespace, '\\') . '\\'; //处理命名空间
        }
        $this->namespace = $namespaces;
    }

    //携带前置执行
    public function withPre($pre): object
    {
        $this->with($pre, 'pre');
        return $this;
    }
    //携带后置执行
    public function withPost($post): object
    {
        $this->with($post, 'post');
        return $this;
    }

    /**
     * 创建规则
     * @param  array  $rules   规则数组
     * @param  string $methods 该规则数组属于的请求方法
     * @return bool            是否创建成功
     */
    public function create(array $rules, string $methods = 'GET, POST'): bool
    {
        //规则不可为空
        if (!$rules) {
            return false;
        }

        //生成前置和后置
        $final_rules = ['S' => [], 'D' => [], 'PRE' => [], 'POST' => []];
        if ($this->pre) {
            $pre_id                      = self::$counter['pre']++;
            $pre_rules                   = $this->parse($this->pre, $this->namespace['pre']);
            $this->pre                   = [];
            $final_rules['PRE'][$pre_id] = $pre_rules;
        }
        if ($this->post) {
            $post_id                       = self::$counter['post']++;
            $post_rules                    = $this->parse($this->post, $this->namespace['post']);
            $this->post                    = [];
            $final_rules['POST'][$post_id] = $post_rules;
        }

        //生成主规则
        foreach ($rules as $url => $rule) {
            $r      = preg_match_all('/\{(\w+?)\}/', $url, $params); //匹配动态规则
            $params = $params[1];
            if ($r) {
                $url  = '#^' . preg_replace('/\{(\w+?)\}/', '([^\\#/?]*?)', $url) . '$#'; //将{}替换
                $type = 'D';
            } else {
                $type = 'S';
            }
            $final_rules[$type][$url] = [
                'pre'    => $pre_id ?? null,
                'post'   => $post_id ?? null,
                'main'   => $this->parse($rule, $this->namespace['main']),
                'params' => $params, //动态参数名
            ];
        }
        //解析完后再检查路由是否冲突
        foreach (explode(',', $methods) as $method) {
            $method = strtoupper(trim($method));
            $this->check($final_rules, $method); //检查路由是否冲突
            if (isset(self::$rules[$method])) {
                foreach ($final_rules as $type => $value) {
                    self::$rules[$method][$type] = self::$rules[$method][$type] + $value; //用+保持索引
                }
            } else {
                self::$rules[$method] = $final_rules;
            }
        }
        return true;
    }

    /*****内部友源方法*****/
    public static function getRules()
    {
        return self::$rules; //获得路由规则
    }
    public static function reset()
    {
        self::$rules = [];
    }

    //写入本次规则的前置和后置
    private function with($rule, $type)
    {
        if (is_array($rule)) {
            $this->$type = array_merge($this->$type, $rule);
        } else {
            $this->$type[] = $rule;
        }
    }

    //解析执行规则
    private function parse($rules, $namespace)
    {
        if (!is_array($rules)) {
            $rules = [$rules];
        }
        foreach ($rules as $index => $rule) {
            if ($rule instanceof Closure) {
                $rule = [$rule];
            } else {
                $rule    = explode('.', $rule);
                $rule[0] = preg_replace('/\//', '\\', $rule[0]); //将存在的子目录转换为命名空间分隔符
                if ($rule[0] != '\\') {
                    $rule[0] = $namespace . $rule[0]; //非根命名空间补上命名空间
                }
                if (count($rule) < 2) {
                    $this->exception('路由规则需用"."分割');
                }
            }
            $rules[$index] = $rule;
        }
        return $rules;
    }

    private function check($final_rules, $method)
    {

        //检查静态路由是否已存在
        foreach ($final_rules['S'] as $url => $nothing) {
            if (isset(self::$rules[$method]['S'][$url])) {
                $this->exception('路由规则冲突', "$method: $url");
            }
        }
        //检查动态路由是否已存在
        foreach ($final_rules['D'] as $url => $nothing) {
            if (isset(self::$rules[$method]['D'][$url])) {
                $this->exception('路由规则冲突', "$method: $url");
            }
        }
    }

    private function exception($info, $subInfo = '')
    {
        Linker::Exception()::throw ($info, 1, 'Route', $subInfo);
    }
}
