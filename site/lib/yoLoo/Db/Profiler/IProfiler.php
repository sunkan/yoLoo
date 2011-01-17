<?php
namespace yoLoo\Db\Profiler;

/**
 *
 * @author Sunkan
 */
interface IProfiler
{
    public function queryStart($query,$params);
    public function queryEnd();

    public function isEnabled();
    public function setEnabled();


    public function getQuerys();

    public function getNrOfQuerys();
    public function getTotalExecuteTime();

}