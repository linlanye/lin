<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-11-08 10:37:53
 * @Modified time:      2018-11-08 10:38:21
 * @Depends on Linker:  None
 * @Description:
 */
namespace app\layer;

use lin\layer\Layer;

class Index extends Layer
{
    public function test5()
    {
        $data = 'asd打算啊23爱上24et４ｔｎбве чце★◇←▲＃にほんご';
        $a    = new \lin\algorithms\LSE('adhksgdkg6969');
        $t0   = microtime(true);
        $en   = $a->encrypt($data);

        $t1 = microtime(true);
        echo '----------------------------------' . "<br>";
        echo ('加密：' . $en) . "<br>";
        echo ('加密时间(ms): ' . ($t1 - $t0) * 1000) . "<br>";
        $d = $a->decrypt($en);
        echo "---------------------------------<br>";
        echo ('解密：' . $d) . "<br>";
        echo ('解密时间(ms): ' . (microtime(true) - $t1) * 1000) . "<br>";
        dump($a->decrypt($en));
        $Request = new \lin\basement\request\Request;
        dump($Request::getUploads());
        dump($Request::getUploadsError());
    }
    public function test66()
    {
        $token = Security::build('captcha', 1);
        // $this->test5();
        // \Linker::Event()::trigger('user_event');
        // \Linker::Event()::trigger('user_event', 2, 3);
        // $v = new \app\block\validator\Instruction;
        // var_dump($v->withRule('ruleA')->validate(['big' => 3, 'small' => 2]));
        // $v = new \app\block\validator\Instruction;
        // var_dump($v->withRule('ruleA')->validate(['big' => 3, 'small' => 2]));

        // var_dump($v->withRule('ruleA')->validate(['big' => 3, 'small' => 2]));
        // $v = new \app\block\validator\Instruction;
        // var_dump($v->withRule('ruleA')->validate(['big' => 3, 'small' => 2]));
        // dump($v->getErrors());
        // $view = new \lin\view\View;
        // $view->show('welcome');
        // $f = new \app\block\processor\InstructionFormatter;
        // dump($f->withRule('must,may')->format([]));
        $data = [
            'var' => [
                'var2' => 2,
            ],
            'oth' => 1,
            'a'   => 'a',
            'b'   => 'b',
        ];
        \Linker::Debug()::flag('aa');
        $m = new \app\block\mapper\Example;
        dump($m->withRule('must')->map($data, true));
        for ($i = 0; $i < 10; $i++) {

            $a = \Linker::ServerKV(true);
            $a->set('b', [2, 3, 4], 10);
            $a->get('b' . $i);
        }

        \Linker::Debug()::flag('aa');
        // $a->get('b');
        // $a = new \lin\basement\server\kv\KvMemcache;
        // $a->set('b', [2, 3, 4], 10);
        // $a->get('b' . $i);
        // // $a = [];
        // // for ($i = 0; $i < 10; $i++) {
        // //     $Master    = new \app\block\model\Master;
        // //     $Master->x = 22;
        // //     $a[]      = $Master;
        // // }
        // // \Linker::Debug()::flag('aa');
        // $Master = new \app\block\model\Master(['x' => 2]);
        // \Linker::Debug()::flag('aa');
        // // foreach ($Master as $key => $value) {
        // //     $Master[0]->Slave        = new \app\block\model\Master;
        // //     $Master[0]->Slave->Master = new \app\block\model\Master;
        // // }
        // // \Linker::ServerQueue(true)->push('asd');
        // // dump($Master->toArray());
        // // dump($Master->insert());
        // // dump($Master->toArray());

        // // //
        // // //
        // // // $a = new \ArrayObject([1]);
        // // // $b = new \ArrayObject([1, $a, 'a' => 2]);
        // // // foreach ($b as $key => $value) {
        // // //     dump($key, $value);
        // // // }
        // \Linker::ServerQueue(true)->push('click');
        // \Linker::ServerQueue(true)->multiPush(['click', 213]);
        // \Linker::ServerQueue(true)->pop();
        // \Linker::ServerQueue(true)->pop(3);
        // \Linker::ServerSQL(true)->execute('select * from security');
        // $View = new \lin\view\View;
        // $View->show('index');
        \Linker::Debug()::flag(11);
        \app\block\model\Master::withRelation('Slave')->limit(4)->count();
        $Master = \app\block\model\Master::withRelation('Slave')->limit(4)->select();
        dump($Master->toArray());
        \Linker::Debug()::flag(11);
        // // unset($Master->{0}->r1_id);
        // //
        // // $Master->setStrictTrans();
        // // dump($Master->isStrictTrans());
        // $Master      = new \app\block\model\Master;
        // $Master->mk1 = 1;
        // dump($Master->withRelation('Slave')->insert());

        // dump($Master->isMulti());
        $Master->{0}->mk1           = '311';
        $Master->{1}->mk2           = '22';
        $Master->{2}->mk1           = '1';
        $Master->{3}->mk2           = '33';
        $Master->{1}->Slave[0]->sk1 = '33';
        // // unset($Master->{0}->r1_id);
        dump($Master->withRelation('Slave')->update());

        // $Master = new \app\block\model\Master(['x' => 2]);

        // $Master2          = new \app\block\model\Master(['x' => 3]);
        // $Master->relation = $Master2;

        // // dump($Master->toArray());
        // $Master->withRelation('relation')->insert();
        // dump($Master->toArray());
        // // $Master['x']['y'] = 11111111111;
        // dump($Master);
        // $Master[0]['z'] = 'daaa';
        // dump($Master->toArray());
        // // dump($Master['x']);
        // dump($Master->x);
        // self::layer('Index')->index();

        // self::block('model/Users', $agrs1, $args2);
        // self::blockName('model/Users');

        $data = ['a' => 1];
        $Flow = self::flow([
            'index.test',
            'index.test2',
            'index.test3',
        ], $Master);
        dump($Flow->getDetails());
        dump($Flow->data);
        $this->use('http, kv, sql, queue');
        // Security::withToken(10078)->build('captcha1', 1);
        $a = new Security;
        $a->check('captcha', $token);
        $a->check('captch1a', 1);
        Linker::Log(true)->notice('sa');
        Linker::Lang()::i18n('en');
        Event::trigger('user_event');

        $a = new lin\view\View;
        $a->show('welcome');
    }

    public function test($Flow)
    {
        dump($Flow->data);
        $Flow->data = 2;
        echo 'test';
    }

    public function test2($Flow)
    {
        dump($Flow->data);
        echo 'test2';
        // $Flow->terminal();
    }
    public function test3($Flow)
    {
        dump($Flow->data);
        echo 'test3';
    }

}
