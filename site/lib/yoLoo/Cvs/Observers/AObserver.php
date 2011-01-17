<?php
namespace J\Cvs\Observers;

abstract class AObserver implements IObserver
{
    protected $_db;
    protected $_handler;
    public function setDb(\yoLoo\Db\IConnection $conn)
    {
        $this->_db = $conn;
    }
    public function getHandler()
    {
        return $this->_handler;
    }
    public function setHandler($handler)
    {
        $this->_db = $handler->getDb();
        $this->_handler = $handler;
    }
}