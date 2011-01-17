<?php
namespace yoLoo\View\Helper;

abstract class Base
{
    protected $_view = null;
    protected $_init = false;
    public function setView(\yoLoo\View\IView $view)
    {
        $this->_view = $view;
    }
    public function hasView()
    {
        return ($this->_view!==null);
    }
    public function __construct()
    {
        $this->_init = true;
    }
}
