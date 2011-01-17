<?php
namespace yoLoo\Memcache\Key;

class Set extends Base implements \Countable, \ArrayAccess, \IteratorAggregate
{
    protected $_data = null;
    protected $_type = 0x03;

    protected function _load()
    {
        if ($this->_data === null)
        {
            $this->_data = \unserialize(parent::_load());
        }
        return $this->_data;
    }
    protected function _save()
    {
        return parent::_save(\serialize($this->_data));
    }


    public function add($value)
    {
        if ($this->exists($value))
        {
            return false;
        }
        $this->_data[] = $value;
        return $this->_save();
    }
    public function remove($value)
    {
        foreach($this->_data as $key=>$v)
        {
            if ($v == $value)
            {
                unset($this->_data[$key]);
            }
        }
        $this->_save();

        return $this;
    }
    public function getIterator()
    {
        return new \ArrayObject($this->toArray());
    }
    public function move(Set $set, $value)
    {
        $set->add($value);

        return $this->remove($value);
    }
    public function count()
    {
        return count($this->_load());
    }
    public function exists($value)
    {
        foreach($this->_load() as $key=>$v)
        {
            if ($v==$value)
            {
                return true;
            }
        }
        return false;
    }

    public function toArray(){
        return new \ArrayObject($this->_load());
    }
    public function fromArray(array $data)
    {
        $this->_data = \array_merge($this->_load(),$data);
        $this->_save();
    }


    public function offsetExists($offset) {
        throw new Exception("Offset is not allowed in sets");
    }

    public function offsetGet($offset) {
        throw new Exception("Offset is not allowed in sets");
    }

    public function offsetSet($offset, $value) {
        if ($offset!==null)
        {
            throw new Exception("Offset is not allowed in sets");
        }
        $this->add($value);

        return $value;
    }

    public function offsetUnset($offset) {
        throw new Exception("Offset is not allowed in sets");
    }
}
