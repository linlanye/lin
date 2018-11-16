<?php

/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-11-01 10:07:25
 * @Modified time:      2018-11-01 10:33:43
 * @Depends on Linker:  None
 * @Description:
 */

namespace lin\tests\components\layer\classes;

use lin\layer\Layer;

class Layer2 extends Layer
{

    public function data($data)
    {
        return $data;
    }

    public function testLayerMethod($data)
    {
        return self::layer('Layer1')->data($data);
    }
    public function flow2($Flow)
    {
        $Flow->data .= '2';
    }
    public function testUse($utils)
    {
        $this->use($utils);
    }
}
