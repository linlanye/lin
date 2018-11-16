<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-05-09 22:57:13
 * @Modified time:      2018-09-27 17:07:28
 * @Depends on Linker:  Config
 * @Description:        输出图片验证码
 */
namespace lin\security\structure\captcha;

use Linker;

class Img
{
    private $img; //资源句柄
    private $width;
    private $height;
    private $config;
    public function __construct($config)
    {
        $this->config = Linker::Config()::get('lin')['security']['image'];
        $this->config = array_merge($this->config, $config);
        $this->width  = $this->config['resolution'][0];
        $this->height = $this->config['resolution'][1];
    }
    //输出验证码
    public function show($token)
    {
        if ($token) {
            $token = str_split($token);
        } else {
            $token = $this->createToken($this->config['seed']); //自动生成随机token
        }

        $this->create($token);
        $level = intval($this->config['level']);
        $level = $level > 6 ? 6 : $level;
        switch ($level) {
            case 6:
                $this->createCurve();
            case 5:
                $this->createRectangle();
            case 4:
                $this->createEllipse();
            case 3:
                $this->createChar(true);
            case 2:
                $this->createLine();
            case 1:
                $this->createChar();
        }
        //输出图片
        ob_clean();
        header("Content-Type:image/png");
        header("Cache-Control:no-store, no-cache");
        imagepng($this->img);
        imagedestroy($this->img);

        return implode('', $token);
    }

    //获取使用的验证码字符
    private function createToken($seed)
    {
        $seed = preg_split('/(?<!^)(?!$)/u', $seed); //切分多字节字符
        if (empty($seed)) {
            $seed = ['abcd']; //防止seed为空
        }
        $n                = count($seed) - 1;
        $len              = intval($this->config['length']);
        $len              = $len > 19 ? 20 : $len; //最长20位
        $len < 1 and $len = 1; //保证最少一位
        $data             = [];
        while ($len !== 0) {
            $key    = mt_rand(0, $n);
            $data[] = $seed[$key];
            $len--;
        }
        return $data;
    }

    //创建基本验证码
    private function create(array $seed)
    {
        $length = $this->config['length'];
        $length = $length > 0 ? $length : 1;
        //5.画背景图(可选)
        $nbg = count($this->config['background']);
        if ($nbg > 0) {
            $select    = mt_rand(0, $nbg - 1);
            $this->img = imagecreatefromjpeg($this->config['background'][$select]);
        } else {
            $this->img = imagecreate($this->width, $this->height); //创建一个图层
        }
        $bg = imagecolorallocate($this->img, 255, 255, 255);

        //1.生成字体大小
        $xSize     = intval($this->width / $length); //x方向最大尺寸
        $maxSize   = $this->height > $xSize ? $xSize : $this->height; //取高度和x方向最小
        $minSize   = $maxSize / 2;
        $size      = [];
        $totalSize = 0; //字体的总占用尺寸
        for ($i = 0; $i < $length; $i++) {
            $size[$i] = mt_rand($minSize, $maxSize);
            $totalSize += $size[$i];
        }

        //2.生成旋转角度,最大为｜45｜,平移值都跟随角度和字体大小走
        $rotation = [];
        $trans    = [];
        for ($i = 0; $i < $length; $i++) {
            $rotation[$i] = mt_rand(-30, 30);
            $trans[$i]    = intval(sin($rotation[$i] * 0.01745) * $size[$i]); //角度转弧度，算出其偏移值
        }

        //3.生成偏移量
        $xRemain = $this->width - $totalSize;
        $xTrans  = $yTrans  = [];
        $xTotal  = 0;
        for ($i = 0; $i < $length; $i++) {
            //生成随机偏移
            if ($trans[$i] > 0) {
                $_xTrans = mt_rand(0, $xRemain); //防止偏移越界
            } else {
                $_xTrans = mt_rand(0, $xRemain) + $trans[$i]; //减去向左偏带来的移动
            }
            $xTrans[$i] = $_xTrans + $xTotal; //当前偏移=累计偏移加随机偏移
            $xTotal += $_xTrans + $size[$i]; //累计偏移=随机偏移+当前字体大小
            $xRemain -= $_xTrans; //余下的可用偏移
            $yTrans[$i] = $size[$i] + (mt_rand(0, $this->height - $size[$i])); //y偏移=字体大小+高度余下的可用偏移
        }
        //生成颜色
        $color = [];
        for ($i = 0; $i < $length; $i++) {
            $color[$i] = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)); //字体色
        }

        //4.生成验证码
        $n = count($this->config['ttf']) - 1;

        for ($i = 0; $i < $length; $i++) {
            $ttf = $this->config['ttf'][mt_rand(0, $n)];
            imagettftext($this->img, $size[$i], $rotation[$i], $xTrans[$i], $yTrans[$i], $color[$i], $ttf, $seed[$i]);
        }
    }

    //创建干扰直线
    private function createLine()
    {
        $color = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $n     = $this->width * $this->height / 1001;
        for ($i = 0; $i < $n; $i++) {
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
    }

    //创建干扰字符
    private function createChar($vertical = false)
    {
        $color = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $n     = $this->width * $this->height / 401;
        if ($vertical) {
            for ($i = 0; $i < $n; $i++) {
                $c = chr(mt_rand(21, 126));
                imagecharup($this->img, 1, mt_rand(0, $this->width), mt_rand(0, $this->height), $c, $color);
            }
        } else {
            for ($i = 0; $i < $n; $i++) {
                $c = chr(mt_rand(21, 126));
                imagechar($this->img, 1, mt_rand(0, $this->width), mt_rand(0, $this->height), $c, $color);
            }
        }
    }

    //创建干扰圈
    private function createEllipse()
    {
        $color = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $n     = $this->width * $this->height / 3001;
        $a     = $this->width / 2; //长轴
        $b     = $this->height / 2; //短轴
        for ($i = 0; $i < $n; $i++) {
            imageellipse($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $a), mt_rand(0, $b), $color);
        }
    }

    //创建干扰矩形
    private function createRectangle()
    {
        $color = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $n     = $this->width * $this->height / 4001;
        for ($i = 0; $i < $n; $i++) {
            imagerectangle($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color); //输出矩形
        }
    }

    //创建干扰曲线，随机正弦曲弦
    private function createCurve()
    {
        $color = imagecolorallocate($this->img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $dy    = mt_rand(10, $this->height / 2); //y偏移
        $A     = mt_rand(5, $this->height); //振幅
        $b     = mt_rand(0, 6999) / 999; //相位
        $T     = 1.0 / mt_rand(3, intval($this->width / 13)); //周期
        $r     = mt_rand(-300, 300) / 2000.0; //旋转角度
        if ($A / $dy < 1 && $r > 0) {
            $r = -$r; //确保偏移不会太过越界
        }
        $cos = cos($r);
        $sin = sin($r);
        $n   = intval($this->width / 71); //曲线宽度，最大不超过10
        $n   = $n > 10 ? 10 : $n;
        for ($i = 0; $i < $n; $i++) {
            $j = $this->width * $this->height / 7;
            $j = $j > 10000 ? 10000 : $j;
            $k = 0;
            while ($j > 0) {
                $x = $k + $i; //旋转变换
                $y = $dy + $A * sin($k * $T + $b) + $i;
                imagesetpixel($this->img, $cos * $x - $sin * $y, $sin * $x + $cos * $y, $color);
                $j--;
                $k += 0.1;
            }
        }
    }

}
