<?php
namespace yoLoo\Event;

class Dispatcher
{
    protected $_listeners = array();

    /**
    * Connects a listener to a given event name.
    *
    * @param string  $event      An event name
    * @param mixed   $listener  A PHP callable
    */
    public function connect($event, $listener)
    {
        if (!isset($this->_listeners[$event]))
        {
            $this->_listeners[$event] = array();
        }

        $this->_listeners[$event][] = $listener;
    }

    /**
    * Disconnects a listener for a given event name.
    *
    * @param string   $event      An event name
    * @param mixed    $listener  A PHP callable
    *
    * @return mixed false if listener does not exist, null otherwise
    */
    public function disconnect($event, $listener)
    {
        if (!isset($this->_listeners[$event]))
        {
            return false;
        }

        foreach ($this->_listeners[$event] as $i => $callable)
        {
            if ($listener === $callable)
            {
                unset($this->_listeners[$event][$i]);
            }
        }
    }

    /**
    * Notifies all listeners of a given event.
    *
    * @param sfEvent $event A sfEvent instance
    *
    * @return sfEvent The sfEvent instance
    */
    public function notify(IEvent &$event)
    {
        foreach ($this->getListeners($event->getEventName()) as $listener)
        {

if ($event->getEventName()=='mapper.postFetchData')
{
//echo '<hr>';
//var_dump(get_class($listener[0]),$listener[1]);
//var_dump(is_callable($listener));
//$listener[0]->$listener[1]($event);
//var_dump(call_user_func_array($listener, array($event)));
//echo '<hr>';
}
            if (is_callable($listener))
            {
                
                if (is_array($listener))
                {
                    $listener[0]->$listener[1]($event);
                }
                else
                {
                    call_user_func_array($listener, array($event));
                }
            }
            else
            {
                trigger_error('Invalid callback', \E_USER_WARNING);
            }
        }

        return $event;
    }

    /**
    * Returns true if the given event name has some listeners.
    *
    * @param  string   $event    The event name
    *
    * @return Boolean true if some listeners are connected, false otherwise
    */
    public function hasListeners($event)
    {
        if (!isset($this->_listeners[$event]))
        {
            $this->_listeners[$event] = array();
        }

        return (boolean) count($this->_listeners[$event]);
    }

    /**
    * Returns all listeners associated with a given event name.
    *
    * @param  string   $event    The event name
    *
    * @return array  An array of listeners
    */
    public function getListeners($event)
    {
        if (!isset($this->_listeners[$event]))
        {
            return array();
        }

        return $this->_listeners[$event];
    }
}
