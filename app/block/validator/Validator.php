<?php

namespace app\block\validator;

use lin\validator\Validator as Base;

class Validator extends Base
{

    protected function setting()
    {
        $this->setRule('valid_id', [
            'id: must' => ['isInt', 'id必须为整数'], //创建valid_id规则，对id字段强制校验整型且设置错误信息
        ]);
    }

}
