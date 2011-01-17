<?php
namespace yoLoo\Db\Profiler;

class Basic extends Base
{
    /**
     *
     * @var array
     */
    protected $_querys = array();

    /**
     *
     * @var string key for current query
     */
    protected $_current_query = null;

    /**
     *
     * @var int nr of completeted querys
     */
    protected $_query_count = 0;

    /**
     *
     *
     * @param string $query
     * @param array $params
     * @return Basic
     */
    public function queryStart($query,$params)
    {
        $key = md5(serialize($params).$query);
        $starttime = explode(' ', microtime());
        $starttime =  $starttime[1] + $starttime[0];
        $this->_querys[$key] = array(
            'sql'=>$query,
            'params'=>$params,
            'start'=>$starttime
            );
        $this->_current_query = $key;
        $this->_query_count++;
        return $this;
    }

    /**
     *
     * @return bool
     */
    public function queryEnd()
    {
        if (!isset ($this->_querys[$this->_current_query]))
        {
            return false;
        }
        $endtime = explode(' ', microtime());
        $endtime =  $endtime[1] + $endtime[0];
        $execute_time = $endtime - $this->_querys[$this->_current_query]['start'];
        $this->_querys[$this->_current_query]['end'] = $endtime;
        $this->_querys[$this->_current_query]['execute_time'] = $execute_time;
        $this->_current_query = null;
        return true;
    }

    /**
     *
     * @return int
     */
    public function getNrOfQuerys()
    {
        return $this->_query_count;
    }

    /**
     *
     * @return array
     */
    public function getQuerys()
    {
        return $this->_querys;
    }

    /**
     *
     * @return float executetion time in seconds
     */
    public function getTotalExecuteTime()
    {
        $totalt_time = 0;
        foreach ($this->_querys as $query)
        {
            $totalt_time += $query['execute_time'];
        }
        return $totalt_time;
    }

}