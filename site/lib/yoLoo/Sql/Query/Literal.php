<?php
namespace yoLoo\Sql\Query;

class Literal implements IExpression
{
    protected $_sql;
    public function __construct($sql)
    {
        $this->_sql = $sql;
    }
    public function isMany()
    {
        return is_array($this->_sql);
    }
    public function toSql()
    {
        return is_array($this->_sql) ? implode(', ', $this->_sql) : $this->_sql;
    }
}