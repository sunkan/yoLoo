<?php
namespace yoLoo\Event;

class Event implements IEvent, \ArrayAccess
{
    protected $_context = null;
    protected $_event   = '';
    protected $_params  = array();

    public function __construct($context, $event, $params=array())
    {
        $this->_context = $context;
        $this->_event = $event;
        $this->_params = $params;
    }
    public function getEventName()
    {
        return $this->_event;
    }
    public function getContext()
    {
        return $this->_context;
    }
    public function getParameters()
    {
        return $this->_params;
    }

    /////////////////////////////////

    public function offsetExists($name)
    {
        return array_key_exists($name, $this->_params);
    }

    public function offsetGet($name)
    {
        if (!array_key_exists($name, $this->_params))
        {
            throw new \InvalidArgumentException(sprintf('The event "%s" has no "%s" parameter.', $this->_event, $name));
        }

        return $this->_params[$name];
    }

    public function offsetSet($name, $value)
    {
        $this->_params[$name] = $value;
    }

    public function offsetUnset($name)
    {
        unset($this->_params[$name]);
    }

    public function __call($method, $args)
    {
        if (is_object($this->_context) &&
            method_exists($this->_context, $method))
        {
            return call_user_func_array(array($this->_context), $args);
        }
        throw new \BadMethodCallException('No method by that name on object', 0x901);
    }
}