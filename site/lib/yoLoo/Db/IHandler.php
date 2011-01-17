<?php
namespace yoLoo\Db;

interface IHandler extends IConnection
{
    public function connect();
}
