<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2018-04-25 11:44:39
 * @Modified time:      2018-09-28 21:40:06
 * @Depends on Linker:  Config
 * @Description:        用于存储安全相关的参数
 */
namespace lin\security\structure;

use Linker;
use lin\security\structure\handler\LocalHandler;
use lin\security\structure\handler\SQLHandler;

class Params
{
    private static $status   = 0; //运行状态
    private static $data     = [];
    private static $modified = []; //已修改过的id['id'=>1]
    private static $Driver; //使用的存储驱动

    public static function get($id, $scenario):  ? array
    {
        self::init();
        $id = self::getID($id);
        if ($id === null) {
            return null;
        }

        if (!isset(self::$data[$id])) {
            self::$data[$id] = self::$Driver->read($id); //不存在当前id数据，读取数据库里数据
        }

        return self::$data[$id][$scenario] ?? null;
    }

    //设置数据，data=['scenario'=>[token,create_time,life]]
    public static function set($id, array $data)
    {
        self::init();
        //无id调用临时id
        $id = self::getID($id);
        if ($id === null) {
            self::setTmpID(); //临时id也不存则新写临时id
            $id = self::getID($id);
        }

        if (isset(self::$data[$id])) {
            self::$data[$id] = array_merge(self::$data[$id], $data); //合并多个场景数据
        } else {
            self::$data[$id] = $data;
        }
        self::$modified[$id] = 1; //标记该id的场景部分修改

        return true;
    }

    public static function delete($id, $scenario)
    {
        self::init();
        $id = self::getID($id);
        if ($id === null) {
            return false;
        }
        if (!isset(self::$data[$id])) {
            self::$data[$id] = self::$Driver->read($id); //不存在当前id数据，读取数据库里数据
        }

        //已存在该场景则标记该id，
        if (isset(self::$data[$id][$scenario])) {
            unset(self::$data[$id][$scenario]);
            self::$modified[$id] = 1;
            return true;
        }

        //删除全部
        if ($scenario == '*') {
            self::$data[$id]     = [];
            self::$modified[$id] = 1;
            return true;
        }

        return false; //不存在该场景不执行
    }
    private static function getID($id)
    {
        if ($id !== null) {
            return $id;
        }
        //临时id
        if (!isset($_COOKIE['_LIN_SECURITY_TMP_ID'])) {
            return null;
        }
        return '_tmp_' . md5($_COOKIE['_LIN_SECURITY_TMP_ID']);
    }
    private static function setTmpID()
    {
        $id = uniqid(mt_rand(), true) . '.' . mt_rand(); //临时id，保证短期内唯一就行
        setcookie('_LIN_SECURITY_TMP_ID', $id, 0, '/', '', false, true); //记录原始id的cookie
        $_COOKIE['_LIN_SECURITY_TMP_ID'] = $id;
    }
    private static function init()
    {
        if (self::$status) {
            return;
        }

        //选择使用的驱动
        $config = Linker::Config()::get('lin')['security'];
        $gc     = $config['gc'];
        if ($gc['probability'] > 1) {
            $gc['probability'] = 1;
        } else if ($gc['probability'] < 0) {
            $gc['probability'] = 0;
        }

        if ($config['use'] == 'sql') {
            self::$Driver = new SQLHandler($config['server']['sql'], $gc);
        } else {
            self::$Driver = new LocalHandler($config['server']['local'], $gc);
        }

        //注册数据维护
        register_shutdown_function(['\\lin\\security\\structure\\Params', 'close']);
        self::$status = 1;
    }

    //运行关闭后数据维护
    public static function close()
    {
        if (empty(self::$modified)) {
            return;
        }

        $now  = time();
        $data = array_intersect_key(self::$data, self::$modified); //获得已修改数据
        foreach ($data as $id => $item) {
            foreach ($item as $scenario => $v) {
                if ($v[2] > 0 && $v[1] + $v[2] < $now) {
                    unset($data[$id][$scenario]); //尝试剔除过期数据
                }
            }
        }
        if ($tmp_id = self::getID(null)) {
            if (isset($data[$tmp_id])) {
                self::$Driver->writeTmp($tmp_id, $data[$tmp_id]); //写临时客户端
                unset($data[$tmp_id]);
            }
        }

        if ($data) {
            self::$Driver->write($data);
        }

    }

}
