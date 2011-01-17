<?php
namespace yoLoo\Db\Profiler;

abstract class Base extends \yoLoo\Base implements IProfiler
{
    /**
     *
     * @var bool
     */
    protected $_enabled = false;

    /**
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }
    /**
     *
     * @param bool $flag
     * @return Base
     */
    public function setEnabled($flag = true)
    {
        $this->_enabled = $flag;
        
        return $this;
    }


}