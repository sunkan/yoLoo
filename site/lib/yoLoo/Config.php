<?php
namespace yoLoo;

class Config
{
    protected static $_store = array();
    protected static $_build = array();

    public static function load($spec)
    {
        // load the config file values.
        // use alternate config source if one is given.
        if (empty($spec))
        {
            $config = array();
        }
        elseif (is_array($spec) || is_object($spec))
        {
            $config = (array) $spec;
        }
        elseif (is_string($spec))
        {
            $config = (array)(include($spec));
        }
        else
        {
            $config = array();
        }

        self::$_store = $config;
        self::$_build = array();
//        $callback = Solar_Config::get('Solar_Config', 'load_callback');
//        if ($callback)
//        {
//            $merge = (array) call_user_func($callback);
//            Solar_Config::$_store = array_merge(Solar_Config::$_store, $merge);
//        }
    }

    public static function get($class,$key=null,$val=array())
    {
        $class = str_replace('\\', '_', $class);
        // are we looking for a class?
        if ($class === null) {
            // return the whole config array
            return self::$_store;
        }

        // are we looking for a key in the class?
        if ($key === null) {

            // looking for a class. if no default passed, set up an
            // empty array.
            if ($val === null) {
                $val = array();
            }

            // find the requested class.
            if (! array_key_exists($class, self::$_store)) {
                return $val;
            } else {
                return self::$_store[$class];
            }

        } else {

            // find the requested class and key.
            $exists = array_key_exists($class, self::$_store)
                   && array_key_exists($key, self::$_store[$class]);

            if (! $exists) {
                return $val;
            } else {
                return self::$_store[$class][$key];
            }
        }
    }
    public static function getBuild($class)
    {
        if (array_key_exists($class, self::$_build))
            return self::$_build[$class];
    }
    public static function setBuild($class, $config)
    {
        self::$_build[$class] = (array) $config;
        
    }
}
