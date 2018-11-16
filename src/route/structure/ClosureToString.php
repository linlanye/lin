<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-11-07 08:26:16
 * @Modified time:      2018-11-02 13:47:23
 * @Depends on Linker:  Exception
 * @Description:        将闭包输出成字符串,函数形参为闭包的情况不支持，存在use关键字可能会引发变量引用错误
 */

namespace lin\route\structure;

use Closure;
use Linker;
use ReflectionFunction;

class ClosureToString
{
    private $Ref;
    //生成字符串
    public function getString(Closure $Closure): string
    {
        $this->Ref = new ReflectionFunction($Closure);

        $start = $this->Ref->getStartLine() - 1;
        $end   = $this->Ref->getEndLine() - 1;
        if ($end === $start) {
            Linker::Exception()::throw ('路由无法缓存闭包，闭包占用需超1行', 1, 'Route');
        }
        //闭包首行所在行，有且只有一个闭包
        $array     = file($this->Ref->getFileName());
        $content_s = trim($array[$start]); //首行内容
        $content_e = trim($array[$end]); //末行内容
        $str       = $this->getHeader();
        $str .= $this->getStart($content_s);

        for ($i = $start + 1; $i < $end; $i++) {

            $str .= $this->getBody(trim($array[$i]));
        }
        $str .= $this->getEnd($content_e);
        return $str . '}';
    }

    //获取闭包传参部分，此处受限于php固有输出的信息格式
    private function getHeader()
    {
        $str = 'function(';

        //获取变量信息
        foreach ($this->Ref->getParameters() as $p) {
            //设置参数类型
            if ($p->hasType()) {
                $str .= $p->getType()->__toString() . ' ';
            }
            //设置是否引用
            if ($p->isPassedByReference()) {
                $str .= '&';
            }
            //设置参数名
            $str .= '$' . $p->name;

            //设置参数默认值
            if ($p->isOptional()) {
                $v = $p->getDefaultValue(); //获得参数默认值
                if (is_array($v)) {
                    $v = preg_replace('/[\n\r\t]/', '', var_export($v, true)); //若默认值中出现\n\r\t则会导致bug,但此情况几乎不出现,所以使用此处高效的正则表达式
                } else {
                    $v = var_export($v, true);
                }
                $str .= '=' . $v;
            }
            $str .= ',';
        }
        $str = trim($str, ',') . ')';
        //获取形如use($a)格式信息,此处受限于php的内部输出信息
        if (preg_match('/Bound Variables/', $this->Ref->__toString())) {
            $str .= ' use (';
            preg_match('/Bound Variables \[\d\] {[\n\r]([(\s\S)]+?)}/', $this->Ref->__toString(), $r); //获取核心字符串
            $r = preg_split("/[\n\r]/", $r[1]); //切分获得包含每一个变量的字符串

            //依次找寻变量
            foreach ($r as $v) {
                if (preg_match('/Variable #\d \[ \$(\w+?) \]/', $v, $rr)) {
                    $str .= '$' . $rr[1] . ',';
                }
            }
            $str = trim($str, ',') . ')';
        }

        $str .= '{'; //首部边界
        return $str;
    }
    //获取首行"{"之后且无换行的内容，若"{"写在第二行，则返回空
    private function getStart($content)
    {
        $str = '';
        if (preg_match('/function\s*\(.*\)\s*{(.+)/', $content, $r)) {
            $r = preg_replace('/(\/\/|#).*/', '', $r[1]); //去掉可能注释
            $str .= trim($r);
        }
        return $str;
    }
    //获取函数体，只需要去掉空格"\\"和“#”注释即可
    private function getBody($content)
    {
        $content = preg_replace('/(\/\/|#).+/', '', $content);
        return ltrim(trim($content), '{'); //去掉可能存在句首的{
    }
    private function getEnd($content)
    {
        $str = '';
        if (preg_match('/(.?)}/', $content, $r)) {
            $str .= trim($r[1]);
        }
        return $str;
    }
}
