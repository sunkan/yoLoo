<?php
namespace yoLoo\View\Helper;

class Script extends Base
{
    public function script($src = null, $type = 'text/javascript', $pos = 'bottom')
    {
        if ($src === null)
        {
            return $this;
        }

        return $this->add($src, $type);
    }
    public function render($func=null, $pos='all')
    {
        if (is_string($func) && !is_callable($func))
        {
            $pos = $func;
        }
        if (strcasecmp($pos, 'onload')===0)
        {
            return $this->_onLoadScript;
        }
        $scripts = $this->_scripts;
        if ($pos != 'all')
        {
            $scripts = array_filter($scripts, function($obj) use($pos){
                return ($obj->position == $pos);
            });
        }
        if (!is_callable($func))
        {
            $func = function ($obj){
                $tpl = '<script type="%s" src="%s"></script>'."\n";
                return sprintf($tpl,$obj->type, $obj->src);
            };
        }

        return implode("", array_map($func, $scripts));
    }
    public function add($src, $type = 'text/javascript', $pos = 'bottom')
    {
        $obj = new \stdClass;
        $obj->src = $src;
        $obj->type = $type;
        $obj->position = $pos;
        $this->_scripts[md5($src)] = $obj;

        return $this;
    }
    public function start()
    {
        ob_start();
    }
    public function end()
    {
        $this->_onLoadScript .= ob_get_clean()."\n";
    }

    protected $_scripts = array();

    protected $_onLoadScript = '';
}