<?php
namespace yoLoo\Sql\Query;

class Value extends \yoLoo\Event implements IExpression
{
    protected $_value;
    public function __construct($value)
    {
        $this->_value = $value;
    }
    public function isNull()
    {
        return is_null($this->_value);
    }
    public function isMany()
    {
        return is_array($this->_value);
    }
    public function toSql()
    {
        if (is_null($this->_value))
        {
            return 'NULL';
        }
        if (is_array($this->_value))
        {
            $a = array();
            foreach ($this->_value as $value)
            {
                $this->fireEvent('preQuote','value',$value);
                $a[] = $this->quote($value);
            }
            return implode(', ', $a);
        }
        $value = $this->_value;
        $this->fireEvent('preQuote','value',$value);
        return $this->_value;
    }
}