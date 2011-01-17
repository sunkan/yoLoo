<?php
namespace yoLoo;

class Registry
{
    protected static $_obj = array();

    final private function __construct() {}

    public static function get($name)
    {
        // has the shared object already been loaded?
        if (!self::exists($name))
        {
            throw new Exception("Object with name '$name' not in register", 0x101);
        }

        // was the registration for a lazy-load?
        if (isset(self::$_obj[$name]) && is_array(self::$_obj[$name]))
        {
            $val = self::$_obj[$name];
            $obj = \yoLoo::factory($val[0], $val[1]);
            self::$_obj[$name] = $obj;
        }

        return self::$_obj[$name];
    }

    /**
     *
     * Registers an object under a unique name.
     *
     * @param string $name The name under which to register the object.
     *
     * @param object|string $spec The registry specification.
     *
     * @param mixed $config If lazy-loading, use this as the config.
     *
     * @return void
     *
     * @todo Localize these errors.
     *
     */
    public static function set($name, $spec, $config = null)
    {
        if (Registry::exists($name))
        {
            // name already exists in registry
            $class = get_class(Registry::$_obj[$name]);
            throw new Exception("Object with '$name' of class '$class' already in registry", 0x102);
        }

        // register as an object, or as a class and config?
        if (is_object($spec))
        {
            Registry::$_obj[$name] = $spec;
        }
        elseif (is_string($spec))
        {
            // register a class and config for lazy loading
            Registry::$_obj[$name] = array($spec, $config);
        }
        else
        {
            throw new Exception("Please pass an object, or a class name and a config array", 0x103);
        }
    }

    /**
     *
     * Check to see if an object name already exists in the registry.
     *
     * @param string $name The name to check.
     *
     * @return bool
     *
     */
    public static function exists($name)
    {
        return ! empty(self::$_obj[$name]);
    }

}
