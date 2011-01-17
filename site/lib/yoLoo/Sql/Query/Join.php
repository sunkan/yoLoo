<?php
namespace yoLoo\Sql\Query;

class Join extends Criteria
{
    protected $_type;
    protected $_table;
    protected $_alias;

    public function __construct($table, $type = 'JOIN', $alias = null)
    {
        parent::__construct('AND');
        $this->_table = $table; // TODO: Can a query be added as the join target?
        $this->_type = " ".trim($type)." ";
        $this->_alias = $alias;
    }
    public function toSql()
    {
        if (count($this->criteria) > 0)
        {
            $on = "\nON\n" . \yoLoo\Sql\Query::indent(parent::toSql($func));
        }
        else
        {
            $on = "";
        }

        $table = $this->_table;
        $this->fireEvent('preQuote','name', $table);
        if ($this->_alias)
        {
            $alias = $this->_alias;
            $this->fireEvent('preQuote','name', $alias);
            return $this->_type . $table . " AS " . $alias . $on;
        }
        return $this->_type . $table . $on;
    }
}