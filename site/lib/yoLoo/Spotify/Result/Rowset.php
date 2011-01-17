<?php
namespace yoLoo\Spotify\Result;

class Rowset extends \yoLoo\Base implements \SeekableIterator, \Countable, \ArrayAccess
{
    protected $_rows = array();
    protected $_data = array();
    protected $_count = 0;
    protected $_query = null;
    protected $_pointer = 0;

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
            $this->_rows[$this->_pointer] = $this->_query->loadObject($this->_data[$this->_pointer]);
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
            throw new \yoLoo\Exception\OutOfBounds("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;
    }


    public function getRow($pos)
    {
        $key = $this->key();
        try
        {
            $this->seek($position);
            $row = $this->current();
        }
        catch (\yoLoo\Exception\OutOfBounds $e)
        {
            throw new \yoLoo\Spotify\Result\Exception('No row could be found at position ' . (int) $position);
        }
        $this->seek($key);

        return $row;
    }

    public function __construct($query, $data=array())
    {
        parent::__construct(array());
        $this->_query = $query;
        $this->_data = $data;

        $this->_count = count($this->_data);
    }
}