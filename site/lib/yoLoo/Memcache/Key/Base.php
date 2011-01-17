<?php
namespace yoLoo\Memcache\Key;

abstract class Base
{
    protected static $_conn = null;
    protected $_key = null;
    protected $_payload = null;
    protected $_expire = 0;
    protected $_type = 0x00;

    const TYPE_UNDEFINED = 0x00;
    const TYPE_KEY = 0x01;
    const TYPE_LIST = 0x02;
    const TYPE_SET = 0x03;
    const TYPE_SORTED_SET = 0x04;


    public static function setConnection($conn)
    {
        self::$_conn = $conn;
    }
    public static function getConnection()
    {
        return self::$_conn;
    }


    public function __construct($key, $options=array())
    {
        $this->_key = $key;
//        $this->_load(true);
    }
    public function setKey($key)
    {
        $this->_key = $key;
    }
    public function getKey()
    {
        return $this->_key;
    }
    public function setName($key)
    {
        $this->_key = $key;
    }
    public function getName()
    {
        return $this->_key;
    }
    public function rename($newKey,$overwrite=true)
    {
        $oldKey = $this->_key;
        try
        {
            $this->_key = $newKey;
            if (!$overwrite)
            {
                if (!self::$_conn->get($newKey))
                {
                    return true;
                }
            }
            self::$_conn->set($this->_key, $this->_payload);
        }
        catch (\Exception $e)
        {
            $this->_key = $oldKey;
            return false;
        }
        return true;
    }
    public function getType()
    {
        if ($this->_type===null)
        {
            $this->_type = self::$_conn->get('global:type:'.$this->_key);
        }
        return $this->_type;
    }
    protected function _load($force = false)
    {
        if ($this->_payload===null || $force)
        {
            $this->_payload = self::$_conn->get($this->_key);
        }
        return $this->_payload;
    }
    protected function _save($data)
    {
        if (!$this->exist())
        {
            self::$_conn->set('global:type:'.$this->_key, $this->_type);
        }
        return  self::$_conn->set($this->_key, $data, 0, $this->_expire);
    }
    public function exist()
    {
        return (bool)$this->_load(true);
    }
    public function delete()
    {
        return self::$_conn->delete($this->_key);
    }
    public function expire($time)
    {
        $this->setExpire($time);
    }
    public function setExpire($time,$isTimestamp=false)
    {
        if ($isTimestamp && is_string($time))
        {
            $time = strtotime($time);
        }
        $this->_expire = $time;
    }
}
