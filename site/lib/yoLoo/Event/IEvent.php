<?php
namespace yoLoo\Event;
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Sunkan
 */
interface IEvent
{
    public function getEventName();
    public function getContext();
    public function getParameters();
}