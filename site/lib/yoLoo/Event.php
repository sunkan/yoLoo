<?php
namespace yoLoo;

abstract class Event
{

    protected $_events = array();
    protected $_eventHandlers = array();
    public function addEventHandler($handler)
    {
        if (is_object($handler))
        {
            $this->_eventHandlers[] = $handler;
        }
    }
    public function addEvent($event, $func=null)
    {
        $event = strtolower($event);
        if (!is_array($this->_events[$event]))
        {
            $this->_events[$event] = array();
        }
        $this->_events[$event][] = $func;
    }
    public function removeEvent($event)
    {
        $event = strtolower($event);
        $this->_events[$event] = array();
    }

    public function fireEvent($event)
    {
        $event = strtolower($event);
        if(isset($this->_events[$event]) && count($this->_events[$event]) > 0 )
        {
            $params = func_get_args();
            unset ($params[0]);

            foreach ($this->_eventHandlers as $obj)
            {
                if (is_object($obj) && method_exists($obj, $event))
                {
                    call_user_func_array(array($obj, $event), $params);
                }
            }
            foreach ($this->_events[$event] as $func)
            {
                if(is_callable($func))
                {
                    call_user_func_array($func, $params);
                }

            }
        }
        return ;
    }
}