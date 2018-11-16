<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-07-10 10:47:38
 * @Modified time:      2018-10-23 13:29:18
 * @Depends on Linker:  Config
 * @Description:        测试sql构建器的大部分代表性用法
 */
namespace lin\tests\components\orm;

use Linker;
use lin\orm\structure\SQLCreator;
use PHPUnit\Framework\TestCase;

class SQLCreatorTest extends TestCase
{
    private $Creator;
    protected function setUp()
    {
        $this->Creator = new SQLCreator;
    }

    //测试读，常规方法
    public function testRead1()
    {
        $table = md5(mt_rand());
        $f1    = md5(mt_rand());
        $f2    = md5(mt_rand());

        //select
        $this->Creator->table($table)->select("$f1, $f2"); //情况1
        $sql1 = $this->getSQL();
        $this->Creator->table($table)->fields("$f1, $f2")->select(); //情况2
        $sql2 = $this->getSQL();
        $this->assertSame($sql1, $sql2);
        $this->assertSame("select $f1, $f2 from $table", strtolower($sql1)); //预言语句解析

        //one
        $this->Creator->table($table)->one("$f1, $f2"); //情况1
        $sql1 = $this->getSQL();
        $this->Creator->table($table)->fields("$f1, $f2")->limit(1)->select(); //情况2
        $sql2 = $this->getSQL();
        $this->assertSame($sql1, $sql2);
        $this->assertSame("select $f1, $f2 from $table limit 1", strtolower($sql1));

        //聚合
        $this->Creator->table($table)->max($f1);
        $this->assertSame("select max($f1) from $table", $this->getSQL());

        $this->Creator->table($table)->min($f1);
        $this->assertSame("select min($f1) from $table", $this->getSQL());

        $this->Creator->table($table)->avg($f1);
        $this->assertSame("select avg($f1) from $table", $this->getSQL());

        $this->Creator->table($table)->sum($f1);
        $this->assertSame("select sum($f1) from $table", $this->getSQL());

        $this->Creator->table($table)->count($f1);
        $this->assertSame("select count($f1) from $table", $this->getSQL());
    }

    //测试读的多个方法及其分支情况
    public function testRead2()
    {
        $t1          = md5(mt_rand());
        $t2          = md5(mt_rand());
        $f1          = md5(mt_rand());
        $f2          = md5(mt_rand());
        $id          = mt_rand(0, 999999);
        $page_number = Linker::Config()::lin('orm.page.number');
        $current     = $page_number * ($id - 1);

        //如下构造的情况，可以涵盖可能的分支情况
        $this->Creator->table(function ($Creator) use ($t1, $t2) {
            $Creator->limit(0, 10)->table($t1)->table($t2)->select();
        })->group("$f1")->group("$f2")->order("$f1")->order("$f2")->
            fields("$f1")->fields($f2)->page($id)->select(); //一次性测试多个方法

        $this->assertSame("select $f1, $f2 from (select * from $t1, $t2 limit 0, 10) as lin0 group by $f1, $f2 order by $f1, $f2 limit $current, $page_number", $this->getSQL());
    }

    //测试集合操作
    public function testRead3()
    {
        $t1 = md5(mt_rand());
        $t2 = md5(mt_rand());
        $f1 = md5(mt_rand());
        $f2 = md5(mt_rand());

        //union
        $this->Creator->table($t1)->select()->union('all')->table($t2)->one();
        $this->assertSame("select * from $t1 union all select * from $t2 limit 1", $this->getSQL());

        //intersect
        $this->Creator->table($t1)->select($f1)->intersect()->table($t2)->one($f2);
        $this->assertSame("select $f1 from $t1 intersect select $f2 from $t2 limit 1", $this->getSQL());

        //except
        $this->Creator->table($t1)->table($t2)->select()->except()->table("$t1, $t2")->select($f2);
        $this->assertSame("select * from $t1, $t2 except select $f2 from $t1, $t2", $this->getSQL());
    }

    //测试删除
    public function testDelete()
    {
        $table = md5(mt_rand());
        $this->Creator->table($table)->delete();
        $this->assertSame("delete from $table", $this->getSQL());
    }

    //测试插入
    public function testInsert()
    {
        $table = md5(mt_rand());
        $data  = [md5(mt_rand()) => md5(mt_rand())];
        $this->Creator->table($table)->insert()->execute();
        //情况1
        $this->Creator->table($table)->insert($data);
        $sql1 = $this->getSQL();
        //情况2
        $this->Creator->table($table)->insert(key($data), current($data));
        $sql2 = $this->getSQL();

        //断言
        $this->assertSame($sql1, $sql2); //绑定变量替换后一致
        $k = key($data);
        $v = current($data);
        $this->assertSame("insert into $table ($k) values ($v)", strtolower($sql1)); //目标语句

        //测试replace，和withData方法
        $data2 = [md5(mt_rand()) => md5(mt_rand())];
        $k     = key($data) . ', ' . key($data2);
        $v     = current($data) . ', ' . current($data2);
        $this->Creator->table($table)->replace()->withData($data)->withData($data2);
        $this->assertSame("replace into $table ($k) values ($v)", $this->getSQL());

        //子句情况
        $this->Creator->table($table)->insert(function ($Creator) use ($table) {
            $Creator->table($table)->select();
        });
        $this->assertSame("insert into $table (select * from $table)", $this->getSQL());

        //多字段同数据
        $f  = md5(mt_rand());
        $f2 = md5(mt_rand());
        $v  = md5(mt_rand());
        $this->Creator->table($table)->insert("$f, $f", $v); //同字段容错
        $this->assertSame("insert into $table ($f) values ($v)", $this->getSQL());

        //批量数据
        $this->Creator->table($table)->insert()->withData([["$f, $f2" => $v], ["$f2" => $v, "$f" => $v]]);
        if (strcmp($f, $f2) > 0) {
            $f = "$f2, $f"; //字段排序，按升序
        } else {
            $f = "$f, $f2";
        }
        $this->assertSame("insert into $table ($f) values ($v, $v), ($v, $v)", $this->getSQL());
    }

    //测试更新
    public function testUpdate()
    {
        $table = md5(mt_rand());
        $data  = [md5(mt_rand()) => md5(mt_rand())];
        //情况1
        $this->Creator->table($table)->update($data);
        $sql1 = $this->getSQL();
        //情况2
        $this->Creator->table($table)->update(key($data), current($data));
        $sql2 = $this->getSQL();
        //情况3
        $this->Creator->table($table)->withData($data)->update();
        $sql3 = $this->getSQL();

        //断言
        $this->assertSame($sql1, $sql2); //替换绑定变量后一致
        $this->assertSame($sql1, $sql3);
        $this->assertSame($sql2, $sql3);

        //子句情况
        $f = md5(mt_rand());
        $this->Creator->table($table)->update($f, function ($Creator) use ($table) {
            $Creator->table($table)->select();
        });
        $this->assertSame("update $table set $f=(select * from $table)", $this->getSQL());

        //多字段+运算符
        $f2 = md5(mt_rand());
        $this->Creator->table($table)->update("$f, $f, $f2:-", 1); //同字段容错
        $this->assertSame("update $table set $f=$f-1, $f2=$f2-1", $this->getSQL());
    }

    //测试连接
    public function testJoin()
    {
        $table  = md5(mt_rand());
        $table2 = md5(mt_rand());

        //未指定连接表达式和连接方式
        $this->Creator->table($table)->join($table2)->select();
        $this->assertSame("select * from $table join $table2", $this->getSQL());

        //指定连接表达式，未指定连接方式
        $this->Creator->table($table)->join($table2, "$table.id=$table2.id")->select();
        $this->assertSame("select * from $table join $table2 on $table.id=$table2.id", $this->getSQL());

        //指定连接表达式和连接方式
        $this->Creator->table($table)->join("$table2: inner", "$table.id=$table2.id")->select();
        $this->assertSame("select * from $table inner join $table2 on $table.id=$table2.id", $this->getSQL());

    }

    //测试条件语句
    public function testCondition()
    {
        $table = md5(mt_rand());
        $f     = md5(mt_rand());
        $f2    = md5(mt_rand());
        $f3    = md5(mt_rand());

        //符号运算和基本表达式
        $this->Creator->table($table)->where("$f", 1)->where("$f2:>=", 1)->where(["$f3: <" => 1])->select();
        $this->assertSame("select * from $table where $f=1 and $f2>=1 and $f3<1", $this->getSQL());

        //字符运算、or、and
        $this->Creator->table($table)->where([
            "$f:like" => 1, 'or', "$f2:in" => [1, 2], "$f3:notbetween" => [1, 2],
        ])->select();
        $this->assertSame("select * from $table where $f like 1 or $f2 in (1, 2) and $f3 not between 1 and 2", $this->getSQL());

        //null和exists， exists子句，exits无需字段，加括号条件
        $this->Creator->table($table)->where([
            ":exists" => function ($Creator) use ($table) {
                $Creator->table($table)->select();
            },
            ["$f2: isNull" => 'anything', 'or', "$f3:notExists" => 1],
        ])->select();
        $this->assertSame("select * from $table where exists (select * from $table) and ($f2 is null or not exists 1)", $this->getSQL());

        //子句和多个括号
        $this->Creator->table($table)->where([
            "$f:neq" => 1, 'or', [["$f2" => 1], ["$f3:gt" => 1]],
        ])->select();
        $this->assertSame("select * from $table where $f!=1 or (($f2=1) and ($f3>1))", $this->getSQL());

        //多个字段+子句
        $this->Creator->table($table)->where("$f,$f, $f3:>", function ($Creator) use ($table) {
            $Creator->table($table)->select(); //同字段容错
        })->select();
        $sub = "(select * from $table)";
        $this->assertSame("select * from $table where $f>$sub and $f3>$sub", $this->getSQL());

        //having
        $this->Creator->table($table)->having($f, 1)->group($f2)->select();
        $this->assertSame("select * from $table group by $f2 having $f=1", $this->getSQL());
    }
    //测试快照
    public function testSnapshot()
    {
        $this->Creator->table(md5(mt_rand()));
        $this->Creator->setSnapshot();
        $this->Creator->select();
        $sql1 = $this->getSQL();
        $this->assertNotSame($sql1, $this->getSQL()); //未恢复快照前不一致

        $this->Creator->restoreSnapshot();
        $this->Creator->select();
        $this->assertSame($sql1, $this->getSQL()); //恢复快照后一致
    }

    //获得替换绑定变量后的sql语句
    private function getSQL()
    {
        $this->Creator->execute();
        $sql    = $this->Creator->getSQL();
        $params = $this->Creator->getParameters();
        foreach ($params as $key => $value) {
            $sql = str_replace($key, $value, $sql);
        }
        return strtolower($sql);
    }
}
