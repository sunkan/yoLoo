<?php
namespace yoLoo\Sql\Query;

class Field extends \yoLoo\Event implements IExpression
{
    protected $_name;
    protected $_tablename;
    protected $_columnname;

    public function __construct($name)
    {
        $this->_name = $name;
        if (preg_match('/^(.+)\.(.+)$/', $name, $reg)) {
            $this->_tablename = $reg[1];
            $this->_columnname = $reg[2];
        } else {
            $this->_columnname = $name;
        }
    }
    public function getTablename()
    {
        $this->_tablename;
    }
    public function getColumnname()
    {
        $this->_columnname;
    }
    public function toSql()
    {
        $this->fireEvent('preQuote', 'name', $this->_name);
        return $this->_name;
    }
}