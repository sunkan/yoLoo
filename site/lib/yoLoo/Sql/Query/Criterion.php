<?php
namespace yoLoo\Sql\Query;

class Criterion extends \yoLoo\Event implements ICriteron
{
    protected $_left;
    protected $_right;
    protected $_comparator;

    public function addEventHandler($handler)
    {
        parent::addEventHandler($handler);
        $this->_left->addEventHandler($handler);
        $this->_left->addEventHandler($handler);
    }
    public function addEvent($event, $func=null)
    {
        parent::addEvent($event, $func);
        $this->_left->addEvent($event, $func);
        $this->_left->addEvent($event, $func);
    }

    public function __construct($left, $right, $comparator = '=')
    {
        $this->_left = $left instanceof IExpression ? $left : new Field($left);
        $this->_right = $right instanceof IExpression ? $right : new Value($right);
        $this->_comparator = trim($comparator);
    }
    public function toSql()
    {
        $is_null = method_exists($this->_right, 'isNull') && $this->_right->isNull();
        $is_many = method_exists($this->_right, 'isMany') && $this->_right->isMany();
        if ($is_null)
        {
            if ($this->_comparator === '=')
            {
                return $this->_left->toSql() . ' IS NULL';
            }
            elseif ($this->_comparator === '!=')
            {
                return $this->_left->toSql() . ' IS NOT NULL';
            }
        }
        elseif ($is_many)
        {
            if ($this->_comparator === '=' || $this->_comparator === '!=')
            {
                $right = \yoLoo\Sql\Query::Indent($this->_right->toSql(), true);
                if ($this->_comparator === '=')
                {
                    return $this->_left->toSql() . ' IN (' . $right . ')';
                }
                return $this->_left->toSql() . ' NOT IN (' . $right . ')';
            }
        }
        return $this->_left->toSql() . ' ' . $this->_comparator . ' ' . $this->_right->toSql();
    }
}
