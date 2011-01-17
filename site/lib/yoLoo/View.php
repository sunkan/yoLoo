<?php
namespace yoLoo;

class View extends \yoLoo\Base implements View\IView, View\ICallable
{
    protected $_yoLoo_View = array(
        'template_path'=>array(),
        'escape'=>array()
    );
    protected $_escape = array(
        'function' => 'htmlspecialchars',
        'quotes'   => ENT_COMPAT,
        'charset'  => 'UTF-8'
    );

    protected $_template_path = array();
    protected $_template_file = null;
    protected $_loader = null;

    public function _postConstruct()
    {
        parent::_postConstruct();
        $this->_template_path = $this->_config['template_path'];

        if (! empty($this->_config['escape']['function']))
        {
            $this->_escape['function'] = $this->_config['escape']['function'];
        }
        if (! empty($this->_config['escape']['quotes']))
        {
            $this->_escape['quotes'] = $this->_config['escape']['quotes'];
        }
        if (! empty($this->_config['escape']['charset']))
        {
            $this->_escape['charset'] = $this->_config['escape']['charset'];
        }
        $this->_loader = \yoLoo::getLoader();
    }
    public function callHelper($method, $args)
    {
        $helper = $this->_loader->loadHelper($method);

        if (method_exists($helper,'setView'))
        {
            call_user_func_array(array($helper, 'setView'), array($this) );
        }

        if (is_callable($helper))
        {
            return $helper($args);
        }

        return call_user_func_array(
            array($helper, $method),
            $args
        );
    }
    public function __call($method,  $args)
    {
        return $this->callHelper($method, $args);
    }
    public function escape($var)
    {
        return $this->_escape['function'](
            $var,
            $this->_escape['quotes'],
            $this->_escape['charset']
        );
    }

    public function __set($var,  $value)
    {
        if ($var[0] != '_') {
            $this->$var = $value;
        }
    }
    public function assign($spec, $value = null)
    {
        if (\is_array($spec)) 
        {
            foreach ($spec as $key => $val) 
            {
                if ($key[0] != "_")
                {
                   $this->$key = $val;
                }
            }
            return true;
        } 
        if (\is_object($spec))

        {
            foreach (\get_object_vars($spec) as $key => $val) 
            {
                if ($key[0] != "_")
                {
                    $this->$key = $val;
                }
            }
            return true;
        }
        if (\is_string($spec)) 
        {
            if ($key[0] != "_") 
            {
                $this->$spec = $value;
                return true;
            }
        }
        
        return false;
    }
    public function getTemplatePath()
    {
        return $this->_template_path;
    }
    public function addTemplatePath($path)
    {
        if (!\is_dir($path))
            throw new View\Exception('Template path must be a valid directory');

        if (\substr($path, -1)!=DIRECTORY_SEPARATOR)
            $path .= DIRECTORY_SEPARATOR;

        $this->_template_path[] = $path;
    }
    public function setTemplatePath($path)
    {
        $this->_template_path = array();
        $this->addTemplatePath($path);
    }

    public function setTemplate($file)
    {
        $this->_template_file = $file;
    }

    protected function _scriptName($file)
    {
        $paths = \array_reverse($this->_template_path);

        foreach ($paths as $path)
        {
            if (\file_exists($path.$file))
                return $path.$file;
        }
        throw new View\Exception("Could't find template:".$file);
    }

    public function render($file=null)
    {
        if ($file === null)
        {
            $file = $this->_template_file;
        }
        try
        {
            $event = new \yoLoo\Event\Event($this, 'view.preRender', array());
            $this->_eventDispatcher->notify($event);
            $file = $this->_scriptName($file);
            ob_start();
            require $file;

            $data = ob_get_clean();

            $event = new \yoLoo\Event\Event($this, 'view.postRender', array(
                'output'=>$data
            ));
            $this->_eventDispatcher->notify($event);
            $data = $event['output'];
            return $data;
        }
        catch (View\Exception $e)
        {
            throw $e;
        }
    }
    public function fetch($url)
    {
        return file_get_contents($url);
    }
    public function display($file=null)
    {
        echo $this->render($file);
    }
}
