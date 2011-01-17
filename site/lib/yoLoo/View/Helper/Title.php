<?php
namespace yoLoo\View\Helper;

class Title extends Base
{
    protected $_title = '';

    public function title($spec=null)
    {
        if ($spec === null)
        {
            return $this;
        }
        return $this->add($spec);
    }
    public function add($spec)
    {
        $this->_title = $spec;
        return $this;
    }
    public function render($func = null)
    {
        return $this->_title;
        if ($func === null)
        {
            $func = function ($obj){
                $tpl = '<link %s />'."\n";
                $tpl2 = ' %s="%s" ';
                $str = '';
                foreach ($obj as $key => $value)
                {
                    $str .= sprintf($tpl2, $key, $value);
                }
        	return sprintf($tpl,$str);
            };
        }
        return implode('', array_map($func, $this->_link));
    }
    public function __toString()
    {
        return $this->render();
    }
}