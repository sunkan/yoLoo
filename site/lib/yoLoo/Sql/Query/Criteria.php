<?php
namespace yoLoo\Sql\Query;

class Criteria extends \yoLoo\Event implements ICriteron
{
    protected $_conjunction;
    protected $_criteria = array();

    public function addEventHandler($handler)
    {
        parent::addEventHandler($handler);
        foreach ($this->_criteria as $criteria)
        {
            $criteria->addEventHandler($handler);
        }
    }
    public function addEvent($event, $func=null)
    {
        parent::addEvent($event, $func);
        foreach ($this->_criteria as $criteria)
        {
            $criteria->addEvent($event, $func);
        }
    }
    public function __construct($conjunction = 'OR')
    {
        $this->_conjunction = $conjunction;
    }
    public function addCriterion($left, $right = null, $comparator = '=')
    {
        return $this->addCriterionObject($left instanceof ICriteron ? $left : new Criterion($left, $right, $comparator));
    }
    public function addConstraint($left, $right, $comparator = '=')
    {
        return $this->addCriterionObject(new Criterion(new Field($left), new Field($right), $comparator));
    }
    public function addCriterionObject(ICriteron $criterion)
    {
        $this->_criteria[] = $criterion;
        return $criterion;
    }
    public function setConjunctionAnd()
    {
        $this->_conjunction = 'AND';
    }
    public function setConjunctionOr()
    {
        $this->_conjunction = 'OR';
    }
    public function toSql()
    {
        if (count($this->_criteria) === 0)
        {
            return '';
        }
        $criteria = array();
        foreach ($this->_criteria as $criterion)
        {
            $criteria[] = $criterion->toSQL();
        }
        return implode("\n" . $this->_conjunction . ' ', $criteria);
    }
}
