<?php
namespace yoLoo\Collection;


abstract class ACollection extends \yoLoo\Base implements ICollection
{
    protected $_rows = array();
    protected $_data = array();
    protected $_count = 0;
    protected $_pointer = 0;

    public function __construct($data, $options=array())
    {
        parent::__construct($options);
        $this->_data = $data;
        $this->_count = count($this->_data);
    }
    public function append($row)
    {
        $this->_data[] = $row;
        $this->_count = count($this->_data);
    }
    public function count()
    {
        return $this->_count;
    }
    public function offsetExists($index)
    {
        return isset($this->_data[(int) $index]);
    }
    public function offsetGet($index)
    {
        $this->_pointer = (int) $index;

        return $this->current();
    }
    public function offsetSet($index, $newval){}
    public function offsetUnset($index){}

    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }

    public function current()
    {
        if ($this->valid() === false)
        {
            return null;
        }

        // do we already have a row object for this position?
        if (empty($this->_rows[$this->_pointer]))
        {
            $this->_rows[$this->_pointer] = $this->loadIndex($this->_pointer);
        }

        // return the row object
        return $this->_rows[$this->_pointer];

    }
    public function key()
    {
        return $this->_pointer;
    }
    public function next()
    {
        ++$this->_pointer;
    }
    public function valid()
    {
        return $this->_pointer < $this->_count;
    }
    public function seek($pos)
    {
        $position = (int) $position;
        if ($position < 0 || $position > $this->_count)
        {
            throw new \yoLoo\Collection\Exception\OutOfBounds("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;
    }

    public function toArray($conf=array())
    {
        if(count($conf)==0)
        {
            $conf = $this->_config;
        }
        for ($i=0;$i<$this->_count;$i++)
        {
            if (empty($this->_rows[$i]))
            {
                $this->_rows[$i] = $this->loadIndex($i, $conf);
            }
        }
        return $this->_rows;
    }
    public abstract function loadIndex($index);
}