<?php
namespace yoLoo;

class Loader
{
    public static function registerAutoloader()
    {
        spl_autoload_register(array('\\yoLoo\\Loader', 'autoload'));
    }
    public function registerNamespace()
    {
        
    }
    public function unregisterNamespace()
    {
        
    }
    public function getRegisteredNamespaces()
    {
        
    }
    public function loadLibrary()
    {
        
    }
    
    public function loadClass()
    {
        
    }
    public function loadJsonCache($file){
        $path = '/home/jesper/site/cache/json/'.$file;
        return json_decode(file_get_contents($path));
    }
    protected $_config_paths = array();
    protected $_loaded_configs = array();
    public function addConfigPath($path)
    {
        array_push($this->_config_paths, $path);

        return $this;
    }
    public function loadConfig($file)
    {
        $key = md5($file);
        if (isset( $this->_loaded_configs[$key]))
        {
            return $this->_loaded_configs[$key];
        }

        foreach (array_reverse($this->_config_paths) as $path)
        {
            $fullPath = $path.$file;

            if(($target = \yoLoo::exists($fullPath)))
            {
                $this->_loaded_configs[$key] = include $target;
            }
        }
        if (isset( $this->_loaded_configs[$key]))
        {
            return $this->_loaded_configs[$key];
        }
        throw new \yoLoo\Exception('No helper by that name');
    }

    protected $_loaded_helpers = array();
    protected $_helper_namespace = array('\\yoLoo\\View\\Helper\\');
    public function registerHelperNamespace($namespace)
    {
        array_push($this->_helper_namespace, $namespace);
        return $this;
    }
    
    public function loadHelper($helper)
    {
        $helper = ucfirst($helper);
        if (isset( $this->_loaded_helpers[$helper]))
        {
            return $this->_loaded_helpers[$helper];
        }
        
        foreach (array_reverse($this->_helper_namespace) as $namespace)
        {
            $class = $namespace.$helper;
            try 
            {
                self::autoload($class, false);
                $this->_loaded_helpers[$helper] = new $class();
            }
            catch (\yoLoo\Exception $e)
            {
                if (is_callable($class))
                {
                    $this->_loaded_helpers[$helper] = new View\Helper($class);
                }
            }
        }
        if (isset( $this->_loaded_helpers[$helper]))
        {
            return $this->_loaded_helpers[$helper];
        }
        throw new \yoLoo\Exception('No helper by that name');
    }
    static $_class_loaded = array();
    public function addLoadedClasses(array $classes)
    {
        self::$_class_loaded = array_merge(self::$_class_loaded, $classes);
    }
    public static function autoload($name)
    {
        if (isset(self::$_class_loaded[$name]))
            return;

        if (class_exists($name, false) || interface_exists($name, false))
            return;

        $file = str_replace(array('\\','_'), DIRECTORY_SEPARATOR, $name) . '.php';
        if(($target = \yoLoo::exists($file)))
            include_once $target;

        if (!(class_exists($name, false) || interface_exists($name, false)))
        {
            self::autoload('yoLoo\\Exception');
            throw new \yoLoo\Exception('Class or interface dose not exist in included file:'.$file);
        }
        self::$_class_loaded[$name] = true;
    }
}
