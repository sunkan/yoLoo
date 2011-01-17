<?php

namespace yoLoo\Db\Mapper;

abstract class CacheBase extends \yoLoo\Db\Mapper\Base
{

    protected $_yoLoo_Db_Mapper_CacheBase = array(
    );

    protected $_cacheRatio = 1;
    protected $_limit = 10;
    protected $_page = 1;

    public function setCache(\yoLoo\Cache\Adapter\Base $cache)
    {
        $this->_cache = $cache;
    }

    public function setCacheRatio($ratio)
    {
        $ratio = (int)$ratio;
        if($ratio<1)
            $ratio = 1;

        $this->_cacheRatio = $ratio;
    }
    public function getCacheRatio()
    {
        return $this->_cacheRatio;
    }


    protected $_cacheKey = null;
    protected function _createKey($sql)
    {

    }

    public function findBySql($sql, $params=array(), $options=array())
    {
        if(isset ($options['cache_key']))
            $this->_cacheKey = $options['cache_key'];

        if(isset ($options['life']))
            $this->_cache->setLife($options['life']);

        if($this->_cacheKey===null)
            $this->_createKey($sql,$params);

        if($this->_cacheRatio > 1)
            $this->_cacheKey .= $this->_getCacheOffsetKey();


        if (!($data = $this->_cache->fetch($this->_cachekey)))
        {
            $stmt = $this->_execute($sql, $params);
            if ($stmt !== false && $stmt !== null)
            {
                $data = $stmt->fetchAll(\PDO::FETCH_OBJ);
                $this->_cache->save($this->_cacheKey, $data);
            }
        }
        $data =  $this->_sliceResult($data);
        if(count($data)==1)
            return $this->loadObject($data[0]);

        return new \yoLoo\Db\Mapper\Rowset($this, $data);
    }


    protected function _getCacheOffsetKey()
    {
        $offset = 0;
        $page = $this->_page;

        $limit = $limit*$this->_cacheRatio;
        $offset = $limit*ceil($page/$this->_cacheRatio)-$limit;

        return $offset.':'.$offset+$limit;
    }

    protected function _sliceResult($rows)
    {
        $offset = ($this->_page-1)*$this->_limit;

        if($offset >= $this->_limit*$this->_cacheRatio)
            $offset = $offset - $this->_limit*$this->_cacheRatio;

        return array_slice($rows, $offset, $this->_limit);
    }
}