<?php
namespace yoLoo\I18n;

class Translation extends Base
{
    protected $_yoLoo_I18n_Translation = array(
        'path'=>'./',
        'cache'=> '\\yoLoo\\Cache\\Adapter\\None',
        'show_keys'=>false,
        'default_languge'=>'se'
    );

    protected $_cache = null;
    protected $_file = null;
    protected $_translation_table = array();

    protected $_is_loaded = false;

    public function __construct($file, $options=array())
    {
        $this->_file = $file;
        parent::__construct($options);
    }

    public function setShowKeys($flag=true)
    {
        $this->_config['show_keys'] = (bool)$flag;
    }

    protected function _postConstruct()
    {
        if ($this->_cache === null)
        {
            if ($this->_config['cache'] instanceof \yoLoo\Cache\Adapter\Base)
            {
               $this->_cache = $this->_config['cache'];
            }
            elseif(is_string($this->_config['cache']))
            {
                $this->_cache = new $this->_config['cache']();
            }
            else
            {
                $this->_cache = new \yoLoo\Cache\Adapter\None();
            }
        }
    }
    protected function _loadFile()
    {
        $path = realpath($this->_config['path']);
        $file = $path.'/'.$this->_file.$this->_lang.'.txt';
        $cache_key = md5($this->_file.$this->_lang.'.txt');

         // fallback to default language if current doesn't exist
         if (!file_exists($file))
         {
            $file = $path.'/'.$this->_file.'.'.$this->_config['default_lang'].'.txt';
            $cache_key = md5($this->_file.'.'.$this->_config['default_lang'].'.txt');
         }

         // after first pass translations are stored in serialised PHP array for speed
         // does a cache exist for the selected language
         if (($data = $this->_cache->fetch($cache_key)))
         {
            // grab current cache
            $cacheData = unserialize($data);

            $this->_translation_table = $cacheData;
            $this->_is_loaded = true;
            return true;
         }
         else
         {
            // create array for serialising
            $cacheData = array();

            // read translation file into array
            $fileData = file($file);

            // does this translation file inherit from another
            $aInheritsMatches = array();
            if (isset($fileData[0]) && preg_match("/^\s*{inherits\s+([^}]+)}.*$/", $fileData[0], $aInheritsMatches))
            {
                $sParentFile = $path.'/'.$this->_file.'.'.trim($aInheritsMatches[1]).'.txt';
                $aParentFile = file($sParentFile);
                // merge lines from parent file into main file array, lines in the main file override lines in the parent
                $fileData = array_merge($aParentFile, $fileData);
                $cacheData['parent-filename'] = $sParentFile;
                $cacheData['parent-timestamp'] = filemtime($sParentFile);
            }
                // read language array line by line
            foreach ($fileData as $line)
            {
                $transMatches = array();

                // match valid translations, strip comments - both on
                // their own lines and at the end of a translation
                // literal hashes (#) should be escaped with a backslash
                if (preg_match("/^\s*([0-9a-z\._-]+)\s*=\s*((\\\\#|[^#])*).*$/iu", $line, $transMatches))
                {
                    $this->_translation_table[$transMatches[1]] = trim(str_replace('\#', '#', $transMatches[2]));
                }
            }
            $cacheData['timestamp'] = filemtime($file);
            $cacheData['translations'] = $this->_translation_table;

            $this->_cache->save(serialize($cacheData));
            $this->_is_loaded = true;
            return true;
        }
        return false;
    }

    public function get($key)
    {
        if (!$this->_is_loaded)
        {
            $this->_loadFile();
        }
        
        $trans = '';

        if (array_key_exists($key, $this->_translation_table))
        { // key / value pair exists
            $trans = $this->_translation_table[$key];

            // number of arguments can be variable as user
            // can pass any number of substitution values
            $iNumArgs = func_num_args();
            $args = func_get_args();
            if ($iNumArgs > 1)
            { // complex translation, substitution values to process
                if (is_array($args[1]))
                { // named substitution variables
                    foreach ($args[1] as $key => $sValue)
                    {
                        $trans = str_replace('{'.$key.'}', $sValue, $trans);
                    }
                }
                else
                { // numbered substitution variables
                    for ($i = 1; $i < $iNumArgs; $i++)
                    {
                        // replace current substitution marker with value
                        $trans = str_replace('{'.($i - 1).'}', $args[$i], $trans);
                    }
                }
            }
            if (!$this->_config['show_keys'])
            {
                return $trans;
            }
        }
        // key / value doesn't exist, show the key instead
        return $key;
    }

}