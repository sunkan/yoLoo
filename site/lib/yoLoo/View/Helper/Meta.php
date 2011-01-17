<?php
namespace yoLoo\View\Helper;

class Meta extends Base
{
    protected $_meta = array();

    public function meta($name=null, $content = '')
    {
        if ($name === null)
        {
            return $this;
        }
        if ($name == 'keywords' || $name == 'description')
        {
            return $this->set($name, $content);
        }
        return $this->add($name, $content);
    }
    public function add($name, $content)
    {
        $obj = array();
        $obj['name'] = $name;
        $obj['content'] = $content;
        $this->_meta[] = $obj;

        return $this;
    }
    public function set($name, $content)
    {
        for($i=0;$i<count($this->_meta);$i++) {
            if ($this->_meta[$i]['name'] == $name)
                unset($this->_meta[$i]);
        }
        return $this->add($name, $content);
    }
    public function render($func = null)
    {
        if ($func === null)
        {
            $func = function ($obj){
                $tpl = '<meta name="%s" content="%s" />'."\n";
                return sprintf($tpl,$obj['name'], $obj['content']);
            };
        }
        return implode('', array_map($func, $this->_meta));
    }
    public function __toString()
    {
        return $this->render();
    }
}
