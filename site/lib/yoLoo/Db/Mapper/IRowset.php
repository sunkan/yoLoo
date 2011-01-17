<?php
namespace yoLoo\Db\Mapper;
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Sunkan
 */
interface IRowset extends \yoLoo\Pager\Collection\ICollection
{
    public function setMapper(Base $mapper);
    public function getMapper();
    public function getRow($pos);
    public function delete();
    public function save();
}