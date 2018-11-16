<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-11-21 10:14:11
 * @Modified time:      2018-11-01 16:55:16
 * @License:            MIT
 * @Description:        基于单向散列的对称加密算法，用于散列的算法可变，命名为lse(lin symmetric(secure) encryption)，本算法为林澜叶个人独创。
 *                      当前使用的单向散列算法为md5，对应16字节密钥，并基于字节进行加密。注：本算法不提供数据校验
 *                      算法原理：选取单向散列算法，其输出固定长为m字节，生成m字节随机偏置b，每一轮的密钥由上一轮的密钥和偏置b经过非线性运算后，将其
 *                      做一次单向散列，得到该轮密钥。数据的每一字节与密钥的16个字节顺序异或，由此得到密文。
 *                      对随机偏置做上诉相同非线性运算(偏置不参与运算)，得到加密后偏置，合起来得到最终密文。解密则先解偏置，再解密文
 */
namespace lin\algorithms;

class LSE
{
    private $key; //初始密钥
    private $times; //循环轮数

    /**
     * 构造函数
     * @param string $secretKey 原始密钥
     * @param int    $times     加密循环次数
     */
    public function __construct(string $secretKey, int $times = 5)
    {
        $this->key   = $this->convert(md5($secretKey, true)); //初始密钥
        $this->times = $times > 1 ? $times : 1;
    }

    /**
     * 加密
     * @param  string $data  原数据
     * @param  bool   $isRaw 是否输出加密原值，否则输出16进制表示
     * @return string|null   加密失败返回null
     */
    public function encrypt(string $data, bool $isRaw = false):  ? string
    {
        //1.数据预处理
        $n = strlen($data);
        if ($n === 0) {
            return null;
        }
        $m       = 16 - $n;
        $padding = false;
        if ($m > 0) {
            for ($i = 0; $i < $m - 1; $i++) {
                $data .= chr(0); //补位0直到15字节
            }
            $data .= chr($m); //最后一位补位长度
            $padding = true;
            $n       = 16; //n的大小置为16
        }

        //2.加密数据
        $b = array(16); //生成16个随机偏置
        for ($i = 0; $i < 16; $i++) {
            $b[$i] = mt_rand(0, 255);
        }
        if ($padding) {
            $b[0] = ($m + $b[1]) % 256; //补位长度存储在偏置中,加上相邻随机偏置，可防止b[0]在同一密钥下不变问题
        }
        $_data = $this->handle($data, $b, $n);

        //3.加密偏置
        $b  = $this->handleB($b);
        $_b = '';
        for ($i = 0; $i < 16; $i++) {
            $_b .= chr($b[$i]); //转化为16个ascii字符
        }
        $b = $_b;

        //4.输出
        if ($isRaw) {
            return $b . $_data; //输出原始数据
        }

        //输出16进制编码数据
        $encrypt = '';
        for ($i = 0; $i < $n; $i++) {
            $encrypt .= $this->hex($_data[$i]); //data和_data长度一样
        }
        $_b = '';
        for ($i = 0; $i < 16; $i++) {
            $_b .= $this->hex($b[$i]);
        }
        return $_b . $encrypt;
    }

    /**
     * 解密
     * @param  string $data  密文
     * @param  bool   $isRaw 密文是否为原始密文，否则已转换为16进制
     * @return string|null   解密失败返回null
     */
    public function decrypt(string $data, bool $isRaw = false) :  ? string
    {
        //1.解密偏置
        $n = strlen($data);
        if ($isRaw) {
            if ($n < 32) {
                return null; //原始数据长度不可能小于32
            }
            $b  = substr($data, 0, 16);
            $_b = array(16);
            for ($i = 0; $i < 16; $i++) {
                $_b[$i] = ord($b[$i]);
            }
        } else {
            if ($n < 64) {
                return null; //16进制长度不可能小于64
            }
            $b  = substr($data, 0, 32); //16进制转化为单字节整数
            $_b = array(16);
            for ($i = 0, $j = 0; $i < 16; $i++, $j += 2) {
                $_b[$i] = hexdec($b[$j] . $b[$j + 1]);
            }
        }
        $b = $this->handleB($_b);

        //2.解密数据
        if ($isRaw) {
            $encrypt = substr($data, 16, $n);
            $n -= 16;
        } else {
            $encrypt = '';
            for ($i = 32; $i < $n; $i += 2) {
                if (isset($data[$i + 1])) {
                    $char = $data[$i] . $data[$i + 1];
                } else {
                    $char = $data[$i];
                }

                $encrypt .= chr(hexdec($char)); //转化data为ascii字符
            }
            $n = ($n - 32) / 2;
        }

        $encrypt = $this->handle($encrypt, $b, $n);

        //3.输出
        if ($n === 16) {
            $m = ord($encrypt[15]); //检查是否有填充数据

            if ($m > 0 && $m < 16 && ($b[0] - $b[1] === $m || $b[0] - $b[1] === $m - 256)) {
                $padding = true;
                while (--$m) {
                    if (ord($encrypt[15 - $m]) !== 0) {
                        $padding = false; //填充位不为0则不是填充
                        break;
                    }
                }
                if ($padding) {
                    $encrypt = substr($encrypt, 0, 16 - ord($encrypt[15]));
                }
            }
        }

        return $encrypt;
    }

    //加密主流程
    private function handle($data, $b, $n)
    {
        $key = $this->key;
        //多次加密
        for ($i = 0; $i < $this->times; $i++) {
            //密钥变换
            $key[16] = &$key[0]; //第17，18个字节用作计算第16个字节
            $key[17] = &$key[1];
            for ($j = 0; $j < 16; $j++) {
                $key[$j] = (($key[$j] + $b[$j]) % 256 & $key[$j + 2]) | ($key[$j + 1] & (~$key[$j + 2])); // 非线性变换，运算公式( i & i+2 ) | ( i+1 & (~(i+2)) )
            }
            $key = md5(implode('', $key), true);
            $key = $this->convert($key);
            //加密
            for ($j = 0, $k = 0; $j < $n; $j++) {
                $tmp = ord($data[$j]);
                $tmp ^= $key[$k]; //每个字符对本轮密钥依次异或
                $k        = (++$k % 16); //确保k在0-15
                $data[$j] = chr($tmp); //单字节整数转ascci
            }
        }
        return $data;
    }
    //加密偏置
    private function handleB($b)
    {
        $key = $this->key;
        //对偏置进行无偏加密
        for ($i = 0; $i < $this->times; $i++) {
            //密钥变换
            $key[16] = &$key[0]; //第17，18个字节用作计算第16个字节
            $key[17] = &$key[1];
            for ($j = 0; $j < 16; $j++) {
                $key[$j] = $key[$j + 1] ^ ($key[$j] | (~$key[$j + 2])); // 非线性变换，运算公式(i+1 ^ ( i | (~(i+2)) )
            }
            $key = md5(implode('', $key), true);
            $key = $this->convert($key);
            //加密
            for ($j = 0; $j < 16; $j++) {
                $b[$j] ^= $key[$j];
            }

        }
        return $b;
    }

    //将key中的每一个ascii字符转为整数
    private function convert($key)
    {
        $_key = array(16);
        for ($i = 0; $i < 16; $i++) {
            $_key[$i] = ord($key[$i]); //转化为数字字符串，每一元素代表一个字节的整数
        }
        return $_key;
    }

    //获取ascii字符的16进制表示
    private function hex($ascii)
    {
        $str = dechex(ord($ascii));
        if (!isset($str[1])) {
            $str = '0' . $str; //补位
        }
        return $str;
    }

}
