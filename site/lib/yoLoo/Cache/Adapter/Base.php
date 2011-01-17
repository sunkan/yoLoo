<?php
namespace yoLoo\Cache\Adapter;

abstract class Base extends \yoLoo\Base
{
    protected $_yoLoo_Cache_Adapter = array(
        'active' => true,
        'life'   => 0,
        'prefix' => null,
    );
    protected $_active = true;
    protected $_life = 0;
    protected $_prefix = 'yoLoo_Cache_Adapter';

    protected function _postConstruct()
    {
        $this->_active = (bool)$this->_config['active'];
        $this->_life = (int)$this->_config['life'];
        $this->_prefix = (string)$this->_config['prefix'];
    }
    
    abstract public function add($key, $value);
    abstract public function delete($key);
    abstract public function deleteAll();
    abstract public function increment($key, $amt = 1);

    public function entry($key)
    {
        return $this->_prefix . $key;
    }

    abstract public function fetch($key);
    
    public function fetchOrAdd($key, $callback, $args)
    {
        return $this->_fetchOrInsert('add', $key, $callback, $args);
    }
    public function fetchOrSave($key, $callback, $args)
    {
        return $this->_fetchOrInsert('save', $key, $callback, $args);
    }

    public function getLife()
    {
        return $this->_life;
    }
    public function setLife($timeout)
    {
        $this->_life = (int)$timeout;
    }

    public function isActive()
    {
        return $this->_active;
    }
    public function setActive($flag)
    {
        $this->_active = (bool)$flag;
        return $this;
    }

    abstract public function save($key, $data);

    protected function _fetchOrInsert($method, $key, $callback, $args = null)
    {
        if ($this->isActive())
        {
            $data = $this->fetch($key);
            if ($data !== false)
                return $data;
        }

        // cache not active, or fetch failed. create the data and insert it.
        $data   = call_user_func_array($callback, (array) $args);
        $result = $this->$method($key, $data);
        if ($result)
            return $data;

        // failed
        throw new yoLoo\Exception();
    }
}
