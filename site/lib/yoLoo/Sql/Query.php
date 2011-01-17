<?php
namespace yoLoo\Sql;

class Query extends Query\Criteria implements Query\IExpression
{
    protected $_tablename;
    protected $_alias;
    protected $_columns = array();
    protected $_joins = array();
    protected $_unions = array();
    protected $_order = array();
    protected $_limit = null;
    protected $_offset = null;
    protected $_groupby = array();
    protected $_having = null;
    protected $_sql_calc_found_rows = false;
    protected $_straight_join = false;

    public static function indent($s, $break_before_multiple_lines = false)
    {
        if ($break_before_multiple_lines && is_int(strpos($s, "\n")))
        {
            return "\n  " . str_replace("\n", "\n  ", $s);
        }
        return "  " . str_replace("\n", "\n  ", $s);
    }

    public function addEventHandler($handler)
    {
        parent::addEventHandler($handler);
        foreach ($this->_joins as $join)
        {
            $join->addEventHandler($handler);
        }
        foreach ($this->_unions as $union)
        {
            $union->addEventHandler($handler);
        }
        foreach ($this->_groupby as $group)
        {
            $group->addEventHandler($handler);
        }
        foreach ($this->_having as $having)
        {
            $having->addEventHandler($handler);
        }
        foreach ($this->_columns as $col)
        {
            $col->addEventHandler($handler);
        }
        foreach ($this->_order as $order)
        {
            $order->addEventHandler($handler);
        }
    }
    public function addEvent($event, $func=null)
    {
        parent::addEvent($event, $func);
        foreach ($this->_joins as $join)
        {
            $join->addEvent($event, $func);
        }
        foreach ($this->_unions as $union)
        {
            $union->addEvent($event, $func);
        }
        foreach ($this->_groupby as $group)
        {
            $group->addEvent($event, $func);
        }
        foreach ($this->_having as $having)
        {
            $having->addEvent($event, $func);
        }
        foreach ($this->_columns as $col)
        {
            $col->addEvent($event, $func);
        }
        foreach ($this->_order as $order)
        {
            $order->addEvent($event, $func);
        }
    }

    public function __construct($tablename, $alias = null)
    {
        parent::__construct();
        $this->_tablename = $tablename;
        $this->_alias = $alias;
    }
    /**
     *
     * @param string or Query\Join  $mixed
     * @param <type> $type
     * @param <type> $alias
     * @return <type>
     */
    public function addJoin($mixed , $type = 'JOIN', $alias = null) {
        if (!($mixed instanceOf Query\Join))
        {
            $mixed = new Query\Join($mixed, $type, $alias);
        }

        return $this->addJoinObject($mixed);
    }
    public function addJoinObject(Query\Join $join)
    {
        $this->_joins[] = $join;
        return $join;
    }
  /**
   * @tip
   *   Some times, WHERE clauses with OR can be optimised, by creating two queries and UNION them together.
   *   See:
   *   http://www.techfounder.net/2008/10/15/optimizing-or-union-operations-in-mysql/
   *
   * @tip
   *   Generally, `UNION ALL` outperforms `UNION`
   *   See:
   *   http://www.mysqlperformanceblog.com/2007/10/05/union-vs-union-all-performance/
   */
    public function addUnion($mixed, $alias = null, $type = 'DISTINCT')
    {
        $union = $mixed instanceOf Query ? $mixed : new Query($mixed, $alias);
        $this->_unions[] = array($union, $type);
        return $union;
    }
    public function addUnionDistinct($mixed, $alias = null)
    {
        return $this->addUnion($mixed, $alias, 'DISTINCT');
    }
    public function addUnionAll($mixed, $alias = null)
    {
        return $this->addUnion($mixed, $alias, 'ALL');
    }
    public function addGroupBy($column)
    {
        $groupby = $column instanceof Query\IExpression ? $column : new Query\Field($column);
        $this->_groupby[] = $groupby;
        return $groupby;
    }
    public function setHaving($left, $right = null, $comparator = '=')
    {
        return $this->_having = $left instanceof Query\ICriteron ? $left : new Query\Criterion($left, $right, $comparator);
    }
    public function addColumn($column, $alias = null)
    {
        $this->_columns[] = array(
            $column instanceof Query\IExpression ? $column : new Query\Field($column),
            $alias
        );
    }
    public function setOrder($order, $direction = null)
    {
        $this->_order = array();
        if ($order != "")
        {
            $this->addOrder($order, $direction);
        }
    }
    public function addOrder($order, $direction = null)
    {
        $this->_order[] = array(
            $order instanceof Query\IExpression ? $order : new Query\Field($order),
            in_array($direction, array('ASC', 'DESC')) ? $direction : null
        );
    }
    public function setLimit($limit)
    {
        $this->_limit = (int) $limit;
    }
    public function setOffset($offset)
    {
        $this->_offset = (int) $offset;
    }
    /**
    * @tip
    *   In most cases, a `select count(*)` query is faster, since it will use the index.
    *   However, if the query would cause a table scan, `sql_calc_found_rows` might perform better.
    *   See:
    *   http://www.mysqlperformanceblog.com/2007/08/28/to-sql_calc_found_rows-or-not-to-sql_calc_found_rows/
    */
    public function setSqlCalcFoundRows($flag = true)
    {
        $this->_sql_calc_found_rows = $flag;
    }
    /**
    * @tip
    *   When `straight_join` is set, MySql will execute joins in the order they are defined.
    *   See:
    *   http://www.daylate.com/2004/05/mysql-straight_join/
    */
    public function setStraightJoin($flag = true)
    {
        $this->_straight_join = $flag;
    }
    public function setQuoteCallback($func)
    {
        if (is_callable($func))
        {
            $this->_quoteCallback = $func;
        }
    }
    protected $_quoteCallback = null;
    public function toSql()
    {
        if ( $this->_quoteCallback !== null )
        {
            $this->addEvent('onQuote', $this->_quoteCallback);
        }
        $sql = 'SELECT';
        if ($this->_sql_calc_found_rows)
        {
            $sql .= ' SQL_CALC_FOUND_ROWS';
        }
        if ($this->_straight_join)
        {
            $sql .= ' STRAIGHT_JOIN';
        }
        $alias = $this->_alias;
    
        if (count($this->_columns) === 0)
        {
            $columns = ' *';
        }
        else
        {
            $columns = array();
            foreach ($this->_columns as $column)
            {
                $columns[] = $column[0]->toSql() . ($column[1] ? (' AS ' . $this->quote($column[1])) : '');
            }
            $columns = (count($columns) === 1 ? ' ' : "\n") . implode(",\n", $columns);
        }
        if ($this->_tablename instanceof Query)
        {
            $sql .= sprintf("%s\nFROM (%s)", $columns, \yoLoo\Sql\Query::indent($this->_tablename->toSql(), true));
            if (!$alias)
            {
                $alias = 'from_sub';
            }
        }
        else
        {
            $table = $this->_tablename;
            $this->fireEvent('preQuote','name',$table);
            $sql .= sprintf("%s\nFROM %s", $columns, $table);
        }
        if ($alias) 
        {
            $this->fireEvent('preQuote','name',$alias);
            $sql.= ' AS ' . $alias;
        }
        foreach ($this->_joins as $join)
        {
            $sql .= "\n" . $join->toSQL();
        }
        if (count($this->criteria) > 0)
        {
            $sql = $sql . "\nWHERE\n" . \yoLoo\Sql\Query::indent(parent::toSql());
        }
        if (count($this->_groupby) > 0)
        {
            $tmp = array();
            foreach ($this->_groupby as $groupby)
            {
                $tmp[] = $groupby->toSql();
            }
            $sql .= "\nGROUP BY\n" . \yoLoo\Sql\Query::indent(implode(",\n", $tmp));
            if ($this->_having)
            {
               $sql .= "\nHAVING\n" . \yoLoo\Sql\Query::indent($this->_having->toSql());
            }
        }
        foreach ($this->_unions as $union)
        {
            $sql .= "\nUNION " . ($union[1] === 'DISTINCT' ? '' : $union[1]) . "\n" . $union[0]->toSQL();
        }
        if (count($this->_order) > 0)
        {
            $order = array();
            foreach ($this->_order as $column)
            {
                $order[] = $column[0]->toSql() . ($column[1] ? (' ' . $column[1]) : '');
            }
            $sql .= "\nORDER BY" . (count($order) === 1 ? ' ' : "\n") . implode(",\n", $order);
        }
        if ($this->_limit)
        {
            $sql .= "\nLIMIT " . $this->_limit;
        }
        if ($this->_offset)
        {
            $sql .= "\nOFFSET " . $this->_offset;
        }
        return $sql;
    }
    public function __toString()
    {
        return $this->toSql();
    }

    public function isMany()
    {
        return true;
    }
}

