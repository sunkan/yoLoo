<?php

class yoLoo
{
    private static $_loader = null;
    private static $_base = '';
    private static $_pkg_base = '';

    public static function init($conf=null)
    {
        self::$_base = __DIR__;
        self::$_pkg_base = dirname(__DIR__);
        self::registerAutoloader();
        yoLoo\Config::load($conf);

        self::$_loader = new yoLoo\Loader();

        $lazy = array(
            'event_dispatcher'=>'\\yoLoo\\Event\\Dispatcher',
        );

        foreach($lazy as $name=>$class)
        {
            \yoLoo\Registry::set($name, $class);
        }
    }

    public static function import($load)
    {
        $path = self::$_pkg_base.'/package/'.$load.'.package';
        if (strpos($load, '*'))
        {
            $path = 'package/'.$load.'package';
            chdir(self::$_base);
            chdir('..');
            $files = glob($path);
            foreach ($files as $file)
            {
                if (yoLoo::exists($file))
                {
                    include ($file);
                }
            }
            return ;
        }

        if (yoLoo::exists($path))
        {
            include ($path);
        }

    }
    public static function getLoader()
    {
        return self::$_loader;
    }
    public static function registerAutoloader($load=null)
    {
        if ($load == null)
        {
//            if (!class_exists('yoLoo\\Loader', false))
  //          {
                require_once 'yoLoo/Loader.php';
    //        }

            spl_autoload_register(array('\\yoLoo\\Loader', 'autoload'));
        }
        elseif (is_callable($load))
        {
            spl_autoload_register($load);
        }

        
    }
    public static function autoload($name)
    {
        if (class_exists($name, false) || interface_exists($name, false))
            return;

        $file = str_replace('\\', DIRECTORY_SEPARATOR, $name) . '.php';
        if(($target = yoLoo::exists($file)))
            include_once $target;

        if (!(class_exists($name, false) || interface_exists($name, false)))
        {
            self::autoload('yoLoo\\Exception');
            throw new yoLoo\Exception('Class or interface dose not exist in included file:'.$file);
        }
    }
    public static function exists($filename)
    {
        // Check for absolute path
        if (realpath($filename) == $filename)
            return $filename;

        // Otherwise, treat as relative path
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $fullPath = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return false;
    }

    public static function factory($class, $config = array())
    {
        if (is_array($class))
        {
            $className = key($class);
            $args = $class;

            $reflection = new ReflectionClass($className);
            $obj        = $reflection->newInstanceArgs($args);
            return $obj;
        }
        $obj = new $class($config);

        // is it an object factory?
        if ($obj instanceof yoLoo\Factory)
        {
            return $obj->factory();
        }
        return $obj;
    }
}
