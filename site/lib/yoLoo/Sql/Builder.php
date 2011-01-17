<?php
namespace \yoLoo\Sql;

class Select extends \yoLoo\Base
{
    protected $_parts = array(
        'distinct' => null,
        'cols'     => array(),
        'from'     => array(),
        'join'     => array(),
        'where'    => array(),
        'group'    => array(),
        'having'   => array(),
        'order'    => array(),
        'limit'    => array(
            'count'  => 0,
            'offset' => 0
        ),
    );


    public function  __toString()
    {

    }

    public function distinct($flag = true)
    {
            if ($flag !== null) {
            $this->_parts['distinct'] = (bool) $flag;
        }
        return $this;
    }

    public function setPaging();
    public function getPaging();
    public function setPage();

    public function cols($cols)
    {
        $this->_addPart(
            'cols',
            null,
            null,
            null,
            null,
            $cols
        );

        return $this;

    }

    protected function _addPart($type)

    public function from();

    public function having();
    public function orHaving();
    public function group();

    public function order();
    public function limit($offset, $limit);

    public function where();
    public function orWhere();

    public function count();
    public function countPages();

}