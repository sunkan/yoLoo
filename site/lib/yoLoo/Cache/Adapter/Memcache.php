<?php
namespace yoLoo\Cache\Adapter;


class Memcache extends Base
{

    protected static $_memcache = false;

    protected $_yoLoo_Cache_Adapter_Memcache = array(
        'host' => 'localhost',
        'port' => 11211,
        'timeout' => 1,
        'pool' => array(),
    );


    protected function _postConstruct()
    {
        parent::_postConstruct();

        if (!self::$_memcache)
        {
            self::$_memcache = new \yoLoo\Memcache(array('servers'=>array($this->_config)));
        }
        $this->setActive(true);
    }

    public function save($key, $data)
    {
\Timer::start('memcache:'.$key);
//echo '<!-- '.(int)$this->_active.' -->';
//        if(!$this->_active)
  //          return ;

        $key = $this->entry($key);
$q = self::$_memcache->set($key,$data, $this->_life);
\Timer::clock('memcache:'.$key);
        return $q;
    }
    public function add($key, $data)
    {
    //    if(!$this->_active)
      //      return ;

        $key = $this->entry($key);

        return self::$_memcache->add($key,$data,$this->_life);
    }
    public function fetch($key)
    {
    //    if(!$this->_active)
      //      return ;
        $key = $this->entry($key);

$q =  self::$_memcache->get($key);
if ($q)
{
//echo '<!-- get:'.$q.' -->';
}
//echo '<!-- '.(int)$q.' -->';
return $q;
    }
    public function increment($key, $amt=1)
    {
    //    if(!$this->_active)
      //      return;

        $key = $this->entry($key);

        $this->add($key,0,$this->_life);
        return self::$_memcache->increment($key,$amt);
    }
    public function delete($key)
    {
    //    if(!$this->_active)
      //      return;

        $key = $this->entry($key);

        return self::$_memcache->delete($key);
    }
    public function deleteAll()
    {
    //    if(!$this->_active)
      //      return;

        return self::$_memcache->flush();
    }
}
