<?php
namespace yoLoo\View\Helper;

class Link extends Base
{
    protected $_link = array();

    public function link($spec=null)
    {
        if ($spec === null)
        {
            return $this;
        }
        return $this->add($spec);
    }
    public function addCss($href, $media='screen')
    {
        $spec['rel']  = 'stylesheet';
        $spec['type'] = 'text/css';
        $spec['href'] = $href;
        $spec['media'] = $media;

        return $this->add($spec);
    }
    public function setCanonical($href)
    {
        $spec['rel']  = 'canonical';
        $spec['href'] = $href;

        return $this->set($spec);
    }
    public function set($spec)
    {
        foreach ($this->_link as $link) {
            if ($link['rel'] == $spec['rel'])
                unset($link);
        }
        $this->add($spec);
    }
    public function add($spec)
    {
        $this->_link[] = $spec;

        return $this;
    }
    public function addFeed($rss, $title)
    {
        $spec['rel']  = 'alternate';
        $spec['type'] = 'application/rss+xml';
        $spec['href'] = $rss;
        $spec['title'] = $title;

        return $this->add($spec);
    }
    public function render($func = null)
    {
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