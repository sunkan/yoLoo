<?php
namespace yoLoo\Db;

class Statement extends \PDOStatement
{
    /**
     *
     * @var \yoLoo\Db\Profiler
     */
    protected $_profiler = null;
    protected $_connection = null;
    protected $_options = array();
    protected $_id = null;
    protected $_eventDispatcher = null;
    protected function __construct($connection=null,$options=array())
    {
        parent::setFetchMode(\PDO::FETCH_OBJ);
        $this->_connection = $connection;
        $this->_options = $options;
        $this->_profiler = $connection->getProfiler();
        $this->_eventDispatcher = \yoLoo\Registry::get('event_dispatcher');
    }
    public function lastInsertId()
    {
        if ($this->_id === null)
        {
            return $this->_id = $this->_connection->lastInsertId();
        }
        return $this->_id;
    }
    public function execute($params=array(),$options=array())
    {
        $event = new \yoLoo\Event\Event($this, 'db.stmt.preExecute', array(
            'sql'=>$this->queryString,
            'params'=>$params,
            'options'=>$options,
            'connection'=>$this->_connection
        ));
        $this->_eventDispatcher->notify($event);

        if ($this->_profiler->isEnabled())
        {
            $this->_profiler->queryStart($this->queryString,$params);
        }
//var_dump($params);
        $rs = parent::execute($params);
        if (substr ( strtolower($this->queryString), 0, 6 )=='insert')
        {
            $this->lastInsertId();
        }
        if ($this->_profiler->isEnabled())
        {
            $this->_profiler->queryEnd();
        }
        $event = new \yoLoo\Event\Event($this, 'db.stmt.postExecute', array(
            'sql'=>$this->queryString,
            'params'=>$params,
            'options'=>$options,
            'connection'=>$this->_connection,
            'rslt'=>$rs
        ));
        $this->_eventDispatcher->notify($event);

        return $rs;
    }
}
