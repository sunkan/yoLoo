<?php
namespace yoLoo\Sphinx\Result;

class Iterator implements \Iterator
{
    private $_position = 0;
    protected $_data = array();
    protected $_mapper = null;

    public function __construct($data=array())
    {
        $this->addData($data);
        $this->_position = 0;
    }

    public function addData(array $data)
    {
        foreach ($data as $key=>$value)
        {
            $this->_data[] = array('id'=>$key,'data'=>$value);
        }
    }

    public function setMapper(yoLoo\Db\Mapper\Base $mapper)
    {
        $this->_mapper = $mapper;
    }

    public function rewind()
    {
        $this->_position = 0;
    }

    public function current()
    {
        if($this->_mapper === null)
            return $this->_data[$this->_position];

        return $this->_mapper->findById($this->_data[$this->_position]['id']);
    }

    public function key()
    {
        return $this->_position;
    }

    public function next()
    {
        ++$this->_position;
    }

    public function valid()
    {
        return isset($this->_data[$this->_position]);
    }
}