<?php
namespace yoLoo\Interfaces;

interface Db
{
    public function prepare($stmt, $options=array());
}

interface DbHandler extends Db
{
    public function connect();
}
