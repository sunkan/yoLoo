<?php
namespace yoLoo\Spotify;

class Result
{
    protected static $_data = null;
    protected static $_resultObject = array();
    protected $_result = null;
    protected $_query = null;

    public function __construct($query, $data)
    {
        $this->_query = $query;
        $keys = $this->_parseData($data);
        $this->_result = new Result\Rowset($this, $keys);
    }
    public function getQuery()
    {
        return $this->_query;
    }
    public function fetch()
    {
        if ($this->_query->isType('lookup'))
        {
            return $this->_result[0];
        }
        if($this->_result->valid())
        {
            $obj = $this->_result->current();
            $this->_result->next();
            return $obj;
        }
        return null;
    }
    public function fetchAll()
    {
        return $this->_result;
    }

    public function loadObject($id)
    {
        if(!isset(self::$_resultObject[$id]))
        {
            self::$_resultObject[$id] = new Result\Object(self::$_data[$id], $this);
        }

        return self::$_resultObject[$id];
    }


    protected function _parseData($data)
    {
        if ($this->_query->isType('lookup'))
        {
            $q = $this->_query->getQuery('q');
            $data->addAttribute('href', $q);

            self::$_data[$q] = $data;
            return array($q);
        }
        $keys = array();
        foreach ($data as $row)
        {
            $keys[] = (string)$row['href'];
            if (!isset(self::$_data[(string)$row['href']]))
            {
                self::$_data[(string)$row['href']] = $row;
            }
        } 
        return $keys;
    }

}