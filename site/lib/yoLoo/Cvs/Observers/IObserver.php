<?php
namespace J\Cvs\Observers;
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Sunkan
 */
interface IObserver
{
    public function accepts($db=null);
    public function update($data);
    public function getHandler();
    public function setHandler($handler);
}
