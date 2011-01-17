<?php
namespace yoLoo;


/**
 * @see Solarphp.com
 */
abstract class Base
{
    protected $_config = array();
    protected $_eventDispatcher = null;

    protected function _preConfig(){}
    protected function _postConfig(){}
    protected function _postConstruct()
    {
        $this->_eventDispatcher = Registry::get('event_dispatcher');
        if (count($this->_config['events']))
        {
            foreach ($this->_config['events'] as $eventName=>$callback)
            {
                $this->_eventDispatcher->connect($eventName,$callback);
                unset($this->_config[$key]);
            }
        }
    }

    public function __construct($config=array())
    {
        $this->setConfig((array)$config);

        $this->_postConstruct();
    }
    public function __destruct() {
        ;
    }
    public function log()
    {

    }
    public function apiVersion()
    {
        return '0.9.0alpha2';
    }
    public function setConfig(array $config)
    {
        $this->_preConfig();
        $this->_config = array_merge(
            $this->_buildConfig(\get_class($this)),
            (array) $config
        );
        $this->_postConfig();
    }
    protected function _buildConfig($class)
    {
        if (! $class) {
            return array();
        }
        $config = \yoLoo\Config::getBuild($class);

        if ($config === null) {
            $var    = '_'.\str_replace('\\', '_', $class);
            $prop   = empty($this->$var)
                    ? array()
                    : (array) $this->$var;

            $parent = get_parent_class($class);

            $config = array_merge(
                // parent values
                $this->_buildConfig($parent),
                // override with class property config
                $prop,
                // override with solar config for the class
                \yoLoo\Config::get($class)
            );
            // cache for future reference
            \yoLoo\Config::setBuild($class, $config);
        }

        return $config;
    }
}
