<?php
namespace yoLoo\Db\Mapper;

class ObjectStub extends Object
{
    public function getId()
    {
        return mt_rand(0,1000);
    }
    protected $_methods = array();
    public function  __call($name,  $arguments) {
        if(key_exists($name, $this->_methods))
        {
            return call_user_func_array($this->_methods[$name],$arguments);
        }
    }
    public function register($name,$func)
    {
        $this->_methods[$name] = $func;
    }
}
