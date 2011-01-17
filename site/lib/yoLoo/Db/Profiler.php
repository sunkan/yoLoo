<?php
namespace yoLoo\Db;
class Profiler extends \yoLoo\Base
{
    protected $_yoLoo_Db_Profiler = array();

    protected $_key = 0;
    protected $_queryCount = 0;
    protected $_enabled = false;
    protected $_logger = null;

    protected $_querys = array();

    public function setLogger()
    {

    }

    public function queryStart($query,$params);
    public function queryEnd();

    public function setEnabled();
    public function getEnabled();

    public function getNrOfQuerys();
    public function getTotalExecuteTime();
}