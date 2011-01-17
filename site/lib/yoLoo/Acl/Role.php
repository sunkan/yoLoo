<?php
namespace yoLoo\Acl;

class Role implements Role\IRole
{
    protected $_parents = array();

    protected $_premissions = array();

    protected $_name = null;

    public function __construct($name, array $prems = array(), array $parents = array())
    {
        $this->_name = $name;
        $this->_premissions = $prems;
        $this->addParents($parents);
    }

    public function is($role)
    {
        if ($role instanceof Role\IRole)
        {
            return ($this->getRoleName()==$role->getRoleName());
        }
        return ($this->getRoleName()==\strtolower($role));
    }
    public function addPremission(Permission\IPermission $prem)
    {
        $this->_premissions[] = $prem;
    }
    public function getParentRoles()
    {
        return $this->_parents;
    }
    public function addParents(array $parents)
    {
        $this->_parents = \array_merge($this->_parents, $parents);
    }

    public function hasPremission($section, $action)
    {
        if($this->getPremission($section, $action)!==false)
            return true;

        foreach ($this->_parents as $role)
        {
            if($role->hasPremission($section,$action))
                return true;
        }

        return false;
    }
    public function getPremission($section, $action)
    {
        foreach ($this->_premissions as $prem)
        {
            if ($prem->is($section, $action))
                return $prem;
        }
        return false;
    }
    public function getRoleName()
    {
        return \strtolower($this->_name);
    }
    public function isAllowed($section, $action=null)
    {
        if( ($prem = $this->getPremission($section, $action)))
        {
            return $prem->isAllowed();
        }
        foreach ($this->_parents as $role)
        {
            if ($role->hasPremission($section,$action))
            {
                return $role->isAllowed($section, $action);
            }
        }
        if($section!=null)
        {
            return $this->isAllowed(null, $action);
        }

        return false;
    }
}