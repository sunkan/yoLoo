<?php
namespace yoLoo\Memcache\Key;

class Key extends Base
{
    protected $_type = 0x01;


    protected $_data = null;
    public function __construct($key, $options=array())
    {
        parent::__construct($key,$options);
    }
    public function setValue($value)
    {
        $this->_data = $value;
        if (\is_array($this->_data))
        {
            return $this->_save(\serialize($this->_data));
        }
        return $this->_save($this->_data);
    }
    public function getValue()
    {
        if ($this->_data === null)
        {
            $this->_data = $this->_load();
            if (substr($this->_data,0,2)== 'a:')
            {
                $this->_data = \unserialize($this->_data);
            }
        }

        return $this->_data;
    }
    
    public function increment($amnt=1)
    {
        return self::$_conn->increment($this->_key, $amnt);
    }
    public function decrement($amnt=1)
    {
        return self::$_conn->decrement($this->_key, $amnt);
    }
}