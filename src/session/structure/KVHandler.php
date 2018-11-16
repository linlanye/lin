<?php
/**
 * @Author:             林澜叶(linlanye)
 * @Contact:            <linlanye@sina.cn>
 * @Date:               2017-04-19 10:32:32
 * @Modified time:      2018-09-03 14:30:22
 * @Depends on Linker:  ServerKV
 * @Description:        使用KV服务器处理session，对bool返回，不能返回false，否则会抛出警告引起安全隐患
 */
namespace lin\session\structure;

use Linker;
use SessionHandlerInterface;

class KVHandler implements SessionHandlerInterface
{
    private $prefix;
    private $life;
    private $Driver;

    public function __construct($config, $life)
    {
        $this->prefix = $config['prefix'];
        $this->life   = (int) $life;
        $this->Driver = Linker::ServerKV(true);
    }
    public function open($save_path, $session_name): bool
    {
        return true;
    }
    public function read($session_id): string
    {
        $r = $this->Driver->get($this->prefix . $session_id);
        if (!$r) {
            return '';
        }
        return $r;
    }
    public function write($session_id, $session_data): bool
    {
        $this->Driver->set($this->prefix . $session_id, $session_data, $this->life);
        return true;
    }

    public function close(): bool
    {
        return true;
    }
    public function destroy($session_id): bool
    {
        $this->Driver->delete($this->prefix . $session_id);
        return true;
    }
    public function gc($maxlifetime): bool
    {
        return true;
    }
}
