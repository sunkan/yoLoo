<?php
namespace yoLoo\Db;

interface IConnection
{
    public function getProfiler();
    public function setProfiler(Profiler\IProfiler $profiler);

    public function prepare($stmt, $options=array());
    public function getQuery($sql = '');

    public function quoteName($name);

    public function isConnected();
    public function connect();
}