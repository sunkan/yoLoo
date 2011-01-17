<?php
namespace yoLoo\Memcache\Key;

class Lista extends Base implements \Countable, \ArrayAccess, \IteratorAggregate
{
    protected $_data = array();
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

    public function get($index)
    {
        $d = $this->_load();
        return $d[$index];
    }
    public function getIterator()
    {
        return new \ArrayObject($this->toArray());
    }

    public function set($index, $value)
    {
        if ($index === null)
        {
            $this->_data[] = $value;
        }
        else
        {
            $this->_data[$index] = $value;
        }
        return $this->_save();
    }

    public function shift()
    {
        return \array_shift($this->_load());
    }

    public function pop()
    {
        return \array_pop($this->_load());
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
    public function move(Lista $list, $value)
    {
        $list[] = $value;

        return $this->remove($value);
    }
    public function count()
    {
        return count($this->_load());
    }
    public function exists($value)
    {
        foreach($this->_data as $key=>$v)
        {
            if ($v==$value)
            {
                return true;
            }
        }
        return false;
    }

    public function append($value)
    {
        $this[] = $value;
        return $this->_save();
    }

    public function prepend($value)
    {
        \array_unshift($this->_data, $value);
        return $this->_save();
    }

    public function truncate($limit, $offset = 0)
    {
        $this->_data = \array_splice($this->_load(), $offset, $limit);
        return $this->_save();
    }


    public function toArray()
    {
        return new \ArrayObject($this->_load());
    }
    public function fromArray(array $data)
    {
        $this->_data = \array_merge($this->_load(),$data);
        $this->_save();
    }


    public function offsetExists($offset)
    {
        $d = $this->_load();
        return isset($d[$offset]);
    }

    public function offsetGet($offset) 
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->set($offset, $value);

        return $value;
    }

    public function offsetUnset($offset) {
        $d = $this->_load();
        unset($d[$offset]);
        return $this->_save();
    }
}