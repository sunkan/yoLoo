<?php
namespace yoLoo\Acl;


class Permission implements Permission\IPermission
{

    protected $_section = null;
    protected $_action = null;
    protected $_type = self::ALLOW;

    public function __construct($section=null, $action=null, $type=self::ALLOW)
    {
        if(\in_array($type, array(self::ALLOW, self::DENY)))
            $this->_type = $type;

        $this->_section = \strtolower($section);
        $this->_action = \strtolower($action);
    }
    public function is($section, $action=null)
    {
        if ($this->_section == \strtolower($section))
        {
            if ($action===$this->_action)
            {
                return true;
            }
            return ($this->_action == \strtolower($action));
        }
        return false;
    }
    public function isAllowed()
    {
        return ($this->_type==self::ALLOW);
    }
}