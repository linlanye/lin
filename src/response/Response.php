<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-08-03 11:44:24
 * @Modified time:      2018-11-02 13:44:37
 * @Depends on Linker:  Config Exception
 * @Description:        提供响应相关的操作
 */
namespace lin\response;

use Linker;
use lin\response\structure\JSONXML;

class Response
{
    private $config;
    private $data = []; //存储分配的数据
    public function __construct()
    {
        $this->config = Linker::Config()::get('lin')['response'];
    }

    /**
     * 设置响应头
     * @param  array|string $header 多个头或单个头
     * @return object               自身
     */
    public function withHeader($header): object
    {
        if (is_array($header)) {
            foreach ($header as $value) {
                header($value);
            }
        } else {
            header($header);
        }
        return $this;
    }

    //设置状态码
    public function withStatus(int $status): object
    {
        http_response_code($status);
        return $this;
    }
    public function withData(array $data): object
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    //跳转到上一页
    public function back(): void
    {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } else {
            $url = '/';
        }
        header("Location: $url");
    }
    /**
     * 显示错误页并跳转
     * @param  string $info      显示信息
     * @param  string $url       跳转地址，为空时跳转到前一页
     * @param  int    $countdown 跳转计数，为空时取默认配置值，小等于0不跳转
     * @return void
     */
    public function error(string $info = '', int $countdown = null, string $url = ''): void
    {
        $this->handlePage($info, $countdown, $url, false);
    }
    //显示正确页并跳转
    public function success(string $info = '', int $countdown = null, string $url = ''): void
    {
        $this->handlePage($info, $countdown, $url, true);
    }
    //跳转到某个url地址
    public function redirect(string $url = '/'): void
    {
        header("Location: $url");
    }

    //响应json
    public function json(string $template = null, int $opt = null, int $depth = null): void
    {
        $config   = $this->config['default']['json'];
        $opt      = $opt === null ? $config['opt'] : $opt;
        $depth    = $depth === null ? $config['depth'] : $depth;
        $template = $template === null ? $config['template'] : $template;

        $this->handleJsonXml($template, true);
        echo json_encode($this->data, $opt, $depth);
        $this->data = []; //清空内置数据
    }
    //响应xml
    public function xml(string $template = null, string $xmlHeader = null): void
    {
        $config    = $this->config['default']['xml'];
        $xmlHeader = $xmlHeader === null ? $config['header'] : $xmlHeader;
        $template  = $template === null ? $config['template'] : $template;

        $this->handleJsonXml($template, false);
        echo $xmlHeader . $this->handleXml($this->data);
        $this->data = []; //清空内置数据
    }

    public function view(string $template = null): void
    {
        call_user_func_array($this->config['view']['method'], [$template, $this->data]);
        $this->data = []; //清空内置数据
    }
    /**
     * 响应下载文件
     * @param  string $file     实际文件名，包含路径
     * @param  string $showName 显示的下载文件名
     * @return void
     */
    public function download(string $file, string $showName = null): void
    {
        $this->handleFile($file, $showName);
    }
    //根据不同文件扩展名显示文件或下载文件
    public function show(string $file, string $showName = null): void
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $this->handleFile($file, $showName, $ext);
    }

    //处理json和xml响应的公共部分
    private function handleJsonXml($template, $isJson)
    {
        //是否使用模版
        if ($template) {
            $file = $this->config['jsonxml']['path'] . "$template.php";
            if (!file_exists($file)) {
                $this->exception('文件不存在', $template);
            }
            $this->data = JSONXML::load($this->data, $file);
        }
        //是否跨域
        if ($this->config['jsonxml']['cross_origin']['on']) {
            header('Access-Control-Allow-Origin: ' . $this->config['jsonxml']['cross_origin']['domain']);
        }
        if ($isJson) {
            $type = 'json';
        } else {
            $type = 'xml';
            if (count($this->data) != 1) {
                $this->data = ['root' => $this->data]; //确保根节点正确
            }
        }
        header("content-type:application/$type;charset=" . $this->config['jsonxml']['charset']); //输出json
    }

    /**
     * /处理文件显示
     * @param  string $file     源文件名
     * @param  string $showName 对用户显示的文件名
     * @param  string $ext      文件的扩展名
     * @return void
     */
    private function handleFile($file, $showName = null, $ext = null)
    {
        $file     = trim($file, '/');
        $showName = $showName ?: basename($file); //默认下载文件名为原文件名
        $file     = rtrim($this->config['file']['path'], '/') . '/' . $file;
        if (!file_exists($file)) {
            $this->exception('文件不存在', $showName);
        }

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                header("filename=$showName");
                break;
            case 'png':
                header('Content-type: image/png');
                header("filename=$showName");
                break;
            case 'gif':
                header('Content-type: image/gif');
                header("filename=$showName");
                break;
            case 'pdf':
                header('Content-type: application/pdf');
                header("filename=$showName");
                break;
            case 'html':
            case 'htm':
            case 'txt':
                header('Content-type: text/html'); //可能会因为编码非utf8导致输出乱码
                header("filename=$showName");
                break;
            default: //默认为下载
                $size = filesize($file); //判断文件大小
                header("Content-type: application/force-download");
                header("Accept-Ranges: bytes");
                header("Content-Length: $size");
                header("Content-Disposition: attachment; filename=\"" . $showName . "\""); //兼容firefox空格文件名问题
        }

        $fp = fopen($file, 'r');
        while (!feof($fp)) {
            echo fread($fp, 2048); //分块传输，此数值2048相对速度快
        }
        fclose($fp);
    }

    //处理成功错误页面显示
    private function handlePage($info, $countdown, $url, $isSuccess)
    {
        if ($isSuccess) {
            $config = $this->config['default']['success'];
            $view   = $this->config['view']['success'];
        } else {
            $config = $this->config['default']['error'];
            $view   = $this->config['view']['error'];
        }
        $info         = $info ?: $config['info'];
        $countdown    = is_null($countdown) ? $config['countdown'] : $countdown;
        $countdown_id = $this->config['view']['countdown_id'];
        //url为空或者前一页不是本站时候，跳回首页
        if (!$url) {
            if (empty($_SERVER['HTTP_REFERER'])) {
                $url = '/';
            } else {
                $url = $_SERVER['HTTP_REFERER'];
                if (strstr($url, $_SERVER['HTTP_HOST']) === false) {
                    $url = '/';
                }
            }
        } //输出倒计时跳转
        $js = <<<EOT
            <script type="text/javascript">
                var countdown='$countdown';
                if (countdown>0) {
                    var timeid=document.getElementById('$countdown_id');
                    timeid.innerHTML='$countdown';
                    (function(){
                        var interval = setInterval(function(){
                            --countdown;
                            --timeid.innerHTML;
                            if(countdown <= 0) {
                                location.href = '$url';
                                clearInterval(interval);
                            };
                        }, 1000);
                    })();
                }
            </script>
EOT;
        include $view;
        echo $js;
    }

    //生成xml字符串
    private function handleXml(array $data)
    {
        $xml = '';
        foreach ($data as $node => $v) {
            if (is_array($v)) {
                $v = $this->handleXml($v);
            }
            $xml .= "<$node>$v</$node>";
        }
        return $xml;
    }
    private function exception($info, $subInfo = '')
    {
        $this->data = [];
        Linker::Exception()::throw ($info, 1, 'Response', $subInfo);
    }
}
