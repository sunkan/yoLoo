<?php
namespace yoLoo\View;

class Helper
{
    private $_func = null;
    public function __construct($func)
    {
        $this->_func = $func;
    }

    public function __invoke()
    {
        $args = func_get_args();
        return call_user_func_array($this->_func, $args);
    }
}