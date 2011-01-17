<?php
namespace yoLoo\View;

class Simple implements IView, ICallable
{
    protected $_file = null;
    public function  __construct($file)
    {
        $this->_file = $file;
        $this->_loader = \yoLoo::getLoader();
    }

    public function callHelper($method, $args)
    {
        $helper = \yoLoo::getLoader()->loadHelper($method);

        if (method_exists($helper,'setView') && !$helper->hasView())
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
        if (method_exists($this, $method))
        {
            return call_user_func_array(array($this,$method), $args);
        }

        return $this->callHelper($method, $args);
    }
    protected function _scriptName($file)
    {
        if (\file_exists($file))
        {
            return $path.$file;
        }
        throw new Exception("Could't find template: ".$file);
    }

    public function render($file=null)
    {
        $file = $this->_file;
        try
        {
            $file = $this->_scriptName($file);
            ob_start();
            require $file;

            $data = ob_get_clean();

            return $data;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $exc) {
            var_dump($exc);
//            echo $exc->getTraceAsString();
        }

    }
}
