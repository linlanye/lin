<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2016-11-13 20:04:02
 * @Modified time:      2018-10-23 13:29:03
 * @Depends on Linker:  Config
 * @Description:        sql解析器，生成完整的sql语句和绑定的变量，注：皆不对表名和字段做转义，设计数据块时应避免使用关键字
 */

namespace lin\orm\structure;

use Closure;
use Linker;
use lin\orm\structure\SQLConditionParser;
use lin\orm\structure\SQLInsertParser;
use lin\orm\structure\SQLUpdateParser;

class SQLCreator
{
    private $container    = []; //参数容器
    private $id           = 0; //当前容器的id
    private $tableCounter = 0; //table为子句时用的计数器，用于区分别名
    private $numberPerPage; //分页方法每页数量
    private $snapshot; //快照信息
    private $Parser = ['c' => '', 'i' => '', 'u' => '']; //条件、插入、更新解析器
    public function __construct()
    {
        $this->addContainer(); //增加一个容器，用于处理当前sql
        $this->numberPerPage = Linker::Config()::get('lin')['orm']['page']['number'];
    }
    //获取当前sql语句
    public function getSQL(): string
    {
        return $this->container[$this->id]['sql'] ?: '';
    }
    //获取绑定变量
    public function getParameters(): array
    {
        return $this->container[$this->id]['params'] ?: [];
    }
    public function reset(): bool
    {
        $this->tableCounter = 0;
        $this->container    = [];
        $this->addContainer();
        return true;
    }
    //设置快照，用于模型类写操作时候，记住中间状态
    public function setSnapshot(): bool
    {
        $this->snapshot = $this->container[$this->id];
        return true;
    }
    //恢复快照
    public function restoreSnapshot(): bool
    {
        if ($this->snapshot) {
            $this->container[$this->id] = $this->snapshot;
        }
        return true;
    }
    /**************/
    /*增删改查操作*/
    /**************/
    public function select(string $fields = ''): object
    {
        $fields && $this->fields($fields);
        $this->container[$this->id]['s']['type'] = 'SELECT';
        return $this;
    }
    public function one(string $fields = ''): object
    {
        $this->limit(1)->select($fields);
        return $this;
    }
    //聚合字段快速查询
    public function max(string $field): object
    {
        $this->handleAggregate("max($field)");
        return $this;
    }
    public function min(string $field): object
    {
        $this->handleAggregate("min($field)");
        return $this;
    }
    public function sum(string $field): object
    {
        $this->handleAggregate("sum($field)");
        return $this;
    }
    public function avg(string $field): object
    {
        $this->handleAggregate("avg($field)");
        return $this;
    }
    public function count(string $field = '*'): object
    {
        $this->handleAggregate("count($field)");
        return $this;
    }
    /**
     * 插入操作，
     * @param  mixed $data  可为数据单元或字段
     * @param  mixed $value 值或者子句，第一个参数为字段时有效
     * @return bool
     */
    public function insert($data = null, $value = null): object
    {
        $this->parseData('INSERT', $data, $value);
        return $this;
    }
    //同插入操作
    public function update($data = null, $value = null): object
    {
        $this->parseData('UPDATE', $data, $value);
        return $this;
    }
    //同插入操作
    public function replace($data = null, $value = null): object
    {
        $this->parseData('REPLACE', $data, $value);
        return $this;
    }
    public function delete(): object
    {
        $this->container[$this->id]['s']['type'] = 'DELETE';
        return $this;
    }

    /*****连贯操作方法，用于生成sql语句的各单元*****/
    public function union(string $type = ''): object
    {
        $this->parseCollection($type, 'UNION');
        return $this;
    }
    public function intersect(string $type = ''): object
    {
        $this->parseCollection($type, 'INTERSECT');
        return $this;
    }
    public function except(string $type = ''): object
    {
        $this->parseCollection($type, 'EXCEPT');
        return $this;
    }
    /**
     * 生成表名
     * @param  string|Closure $table 表名或者子句
     * @return object         $this
     */
    public function table($table): object
    {
        if ($table instanceof Closure) {
            $sql   = $this->getSQLfromClosure($table);
            $table = '(' . $sql . ') as lin' . $this->tableCounter++;
        } else {
            $table = trim($table);
        }
        if (empty($this->container[$this->id]['s']['table'])) {
            $this->container[$this->id]['s']['table'] = " $table";
        } else {
            $this->container[$this->id]['s']['table'] .= ", $table";
        }

        return $this;
    }
    //查询字段，可多次调用增加查询字段
    public function fields(string $fields): object
    {
        if (empty($this->container[$this->id]['s']['fields'])) {
            $this->container[$this->id]['s']['fields'] = "$fields";
        } else if ($this->container[$this->id]['s']['fields'] != '*') {
            $this->container[$this->id]['s']['fields'] .= ",$fields";
        }
        if ($fields == '*') {
            $this->container[$this->id]['s']['fields'] = '*';
        }
        return $this;
    }
    //分组，可多次调用增加分组字段
    public function group(string $group): object
    {
        $group = trim($group);
        if (empty($this->container[$this->id]['s']['group'])) {
            $this->container[$this->id]['s']['group'] = " GROUP BY $group";
        } else {
            $this->container[$this->id]['s']['group'] .= ", $group";
        }
        return $this;
    }
    //排序，可多次调用增加排序字段
    public function order(string $order): object
    {
        $order = trim($order);
        if (empty($this->container[$this->id]['s']['order'])) {
            $this->container[$this->id]['s']['order'] = " ORDER BY $order";
        } else {
            $this->container[$this->id]['s']['order'] .= ", $order";
        }
        return $this;
    }
    //分页
    public function page(int $current): object
    {
        $current = $current > 0 ? $current : 1;
        $this->limit(($current - 1) * $this->numberPerPage, $this->numberPerPage);
        return $this;
    }
    public function limit(int $start, int $num = 0): object
    {
        if ($num > 0) {
            $limit = " LIMIT $start, $num";
        } else {
            if ($start == 0) {
                $limit = ''; //不限制
            } else {
                $limit = " LIMIT $start";
            }
        }
        $this->container[$this->id]['s']['limit'] = $limit;
        return $this;
    }

    /**
     * 条件语句，可多次调用产生多个and条件
     * @param  array|string   $condition  条件单元或者字段
     * @param  scalar|Closure $value      前一个单元为string时，该值为字段值
     * @return object                     $this
     */
    public function where($condition, $value = null): object
    {
        if (!is_array($condition)) {
            $condition = [$condition => $value];
        }
        $this->container[$this->id]['s']['where'] = array_merge($this->container[$this->id]['s']['where'], $condition);
        return $this;
    }
    //同where
    public function having($condition, $value = null): object
    {
        if (!is_array($condition)) {
            $condition = [$condition => $value];
        }
        $this->container[$this->id]['s']['having'] = array_merge($this->container[$this->id]['s']['having'], $condition);
        return $this;
    }
    /**
     * join语句，可多次调用产生多个join，格式为['表名:连接方式(可选)'=>'连接条件']如['table:left outter'=>'table.id=id']
     * @param  array|string   $condition    条件单元或者表名
     * @param  string         $expression   连接条件表达式
     * @return objetc                       $this
     */
    public function join($condition, string $expression = null): object
    {
        if (!is_array($condition)) {
            $condition = [$condition => $expression];
        }
        $this->container[$this->id]['s']['join'] = array_merge($this->container[$this->id]['s']['join'], $condition);
        return $this;
    }

    /**
     * 用于插入更新操作的单个数据分块操作，可多次调用并为一整个数据
     * @param  array $data       数据
     * @param  bool $notOverride 并入数据时，是否覆盖之前的数据，是则用新数据覆盖并入，否则保留之前的老数据后并入
     * @return object
     */
    public function withData(array $data, bool $notOverride = false): object
    {
        if ($notOverride) {
            $this->container[$this->id]['s']['data'] = array_merge($data, $this->container[$this->id]['s']['data']); //不覆写并入数据
        } else {
            $this->container[$this->id]['s']['data'] = array_merge($this->container[$this->id]['s']['data'], $data); //覆写并入数据
        }

        return $this;
    }
    //执行最终处理
    public function execute()
    {
        $table  = $this->container[$this->id]['s']['table'];
        $type   = $this->container[$this->id]['s']['type'];
        $params = [];

        //获得语句参数
        $where = $having = $join = '';
        if ($this->container[$this->id]['s']['where']) {
            $where  = $this->getCondition($this->container[$this->id]['s']['where']);
            $params = array_merge($params, $where[1]);
            $where  = ' WHERE' . $where[0];
        }
        if ($this->container[$this->id]['s']['having']) {
            $having = $this->getCondition($this->container[$this->id]['s']['having']);
            $params = array_merge($params, $having[1]);
            $having = ' HAVING' . $having[0];
        }
        if ($this->container[$this->id]['s']['join']) {
            $join = $this->getJoin();
        }
        //获得语句类型并拼接完整sql
        switch ($type) {
            case 'SELECT':
                $info   = $this->container[$this->id]['s']['info']; //特殊信息
                $order  = $this->container[$this->id]['s']['order'];
                $group  = $this->container[$this->id]['s']['group'];
                $limit  = $this->container[$this->id]['s']['limit'];
                $fields = $this->formatFields();
                $sql    = "${info}SELECT$fields FROM$table$join$where$group$having$order$limit";
                break;
            case 'DELETE':
                $sql = "DELETE FROM$table$join$where";
                break;
            case 'UPDATE':
                if (!$this->Parser['u']) {
                    $this->Parser['u'] = new SQLUpdateParser($this);
                }
                $data   = $this->Parser['u']->handle($this->container[$this->id]['s']['data']);
                $params = array_merge($params, $data[1]);
                $sql    = "UPDATE$table$join SET$data[0]$where";
                break;
            case 'REPLACE':
            case 'INSERT':
                if (!$this->Parser['i']) {
                    $this->Parser['i'] = new SQLInsertParser($this);
                }
                $data   = $this->Parser['i']->handle($this->container[$this->id]['s']['data']);
                $params = array_merge($params, $data[1]);
                $sql    = "$type INTO$table$data[0]$where"; //加入where，防止insert语句调用where时多出绑定变量导致错误，
                break;
            default:
                $sql = '';
        }
        $this->container[$this->id]['sql']    = $sql; //存储sql和绑定变量
        $this->container[$this->id]['params'] = $params;
        $this->clean(); //清空语句数据
    }

    /***************/
    /*****内部解析***/
    /**************/

    //获取当前容器的字段,RMap用
    public function getFields()
    {
        $fields = $this->container[$this->id]['s']['fields'];
        if (!$fields) {
            return null;
        }
        return array_flip(array_map('trim', explode(',', $fields)));
    }

    //获得当前容器用于更新和插入的数据，WMap用
    public function getData()
    {
        return $this->container[$this->id]['s']['data'];
    }
    //解析闭包
    public function getSQLfromClosure(Closure $Closure)
    {
        //为闭包增加一个容器
        $this->addContainer();
        $this->id += 1;
        $Closure($this);
        $this->execute();
        //获取sql并释放容器
        $pid                             = $this->id - 1;
        $sql                             = $this->container[$this->id]['sql'];
        $this->container[$pid]['params'] = array_merge($this->container[$pid]['params'], $this->container[$this->id]['params']);
        unset($this->container[$this->id]);
        $this->id = $pid;
        return $sql;
    }

    private function parseData($type, $data, $value)
    {
        if ($data) {
            if ($data instanceof Closure) {
                $data = [$data];
            } else if (!is_array($data)) {
                $data = [$data => $value];
            }
            $this->container[$this->id]['s']['data'] = array_merge($this->container[$this->id]['s']['data'], $data);
        }
        $this->container[$this->id]['s']['type'] = $type;
    }
    //结合操作，并交差需生成调用前的字句sql
    private function parseCollection($type, $keyword)
    {
        $type = trim($type);
        $type = $type ? "$type " : '';
        $this->select()->execute(); //执行查询
        $this->container[$this->id]['s']['info'] = $this->container[$this->id]['sql'] . " $keyword $type";
    }
    private function formatFields()
    {
        $fields = $this->container[$this->id]['s']['fields'];
        if ($fields) {
            $fields = ' ' . implode(', ', array_unique(array_map('trim', explode(',', $fields)))); //去重
        } else {
            $fields = ' *';
        }
        return $fields;
    }

    //获取条件语句
    private function getCondition($data)
    {
        if (!$this->Parser['c']) {
            $this->Parser['c'] = new SQLConditionParser($this);
        }
        return $this->Parser['c']->handle($data);
    }
    //获取join语句
    private function getJoin()
    {
        $sentence = '';
        foreach ($this->container[$this->id]['s']['join'] as $table => $expression) {
            $table = array_map('trim', explode(':', $table)); //是否指定连接方式
            if (isset($table[1])) {
                $type = ' ' . $table[1];
            } else {
                $type = '';
            }
            $table = $table[0];
            if ($expression) {
                $expression = " ON $expression";
            }
            $sentence .= "$type JOIN $table$expression";
        }
        return $sentence;
    }
    //处理聚合查询
    private function handleAggregate($field)
    {
        $this->fields($field);
        $this->container[$this->id]['s']['type'] = 'SELECT';
    }

    //增加一个容器
    private function addContainer()
    {
        //为当前sql语句分配一个容器，不可用array_push，因为会动态释放，导致索引错误
        $n                   = count($this->container);
        $this->container[$n] = [
            's'      => [
                'table' => '', 'fields' => '', 'limit' => '', 'order' => '', 'group' => '',
                'where' => [], 'having' => [], 'join'  => [], 'data'  => [],
                'type'  => '', 'info'   => '', //type, info: sql类型以及类型信息
            ],
            'sql'    => '', //存放当前容器生成的子句
            'params' => [],
        ];
    }
    //清理当前容器中的语句，不清空sql子句和绑定参数
    private function clean()
    {
        $this->container[$this->id]['s'] = [
            'table' => '', 'fields' => '', 'limit' => '', 'order' => '', 'group' => '',
            'where' => [], 'having' => [], 'join'  => [], 'data'  => [],
            'type'  => '', 'info'   => '', //type, info: sql类型以及类型信息
        ];
    }

}
