<?php
namespace yoLoo\Db\Profiler;

class None extends Base
{
    /**
     *
     * @return bool
     */
    public function isEnabled()
    {
        return false;
    }

    /**
     *
     * @param string $query
     * @param array $params
     * @return None
     */
    public function queryStart($query,$params)
    {
        return $this;
    }

    /**
     *
     * @return bool
     */
    public function queryEnd()
    {
        return false;
    }

    /**
     *
     * @return array
     */
    public function getQuerys()
    {
        return array();
    }

    /**
     *
     * @return int
     */
    public function getNrOfQuerys()
    {
        return 0;
    }

    /**
     *
     * @return int
     */
    public function getTotalExecuteTime()
    {
        return 0;
    }

}