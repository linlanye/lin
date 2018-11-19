<?php
namespace app\block\formatter;

use lin\processor\Formatter as Base;

class Formatter extends Base
{

    protected function setting()
    {
        $this->setRule('must_id', [
            'id: must' => 'toInt', //创建must_id规则，将id强行格式化为整型
        ]);
    }
}
