<?php
namespace yoLoo\Acl;

class Acl extends \yoLoo\Base
{
    /**
     * db => array(
     *       'dns'=>'mysql:localhost;dbname=test;port=3306',
     *       'username'=>'test',
     *       'passwors'=>'l33t'
     * )
     * or
     * db => array('connection'=>new \yoLoo\Db\Connection($conf);
     *
     * @var array
     */
    protected $_yoLoo_Acl_Acl = array(
    );

    /**
     *
     * @var Acl\Role\IRole
     */
    protected $_roles = array();

    protected $_assertions = array();


    public function __construct($config=array())
    {
        parent::__construct($config);
    }

    public function registerAssertion($section, $action, $callback)
    {
        $key = ($section===null?'null':$section).':';
        $key .= ($action===null?'null':$action);

        if(is_callable($callback))
        {
            $this->_assertions[$key][] = $callback;
        }
    }
    protected function _fireAssertion($section, $action)
    {
        $key = ($section===null?'null':$section).':';
        $key .= ($action===null?'null':$action);

        if(isset ($this->_assertions[$key]) &&
            count($this->_assertions[$key]) > 0)
        {
            $args = func_get_args();
            array_unshift($args,$this);
            foreach ($this->_assertions[$key] as $assertion)
            {
                if(!call_user_func_array($assertion, $args))
                {
                    return false;
                }
            }
        }
        return true;
    }

    public function addRole(Role\IRole $role, array $parents = array())
    {
        if ($this->hasRole($role))
        {
            throw new Exception('Role already exist!');
        }
        if(count($parents))
        {
            $parentObjs = array();
            foreach ($parents as $parent)
            {
                if(($roleObj = $this->getRole($parent)))
                {
                    $parentObjs[] = $roleObj;
                }
            }
            $role->addParents($parentObjs);
        }
        $this->_roles[] = $role;
        return $this;
    }

    /**
     *
     * @param <type> $role
     * @return Acl\Role
     */
    public function getRole($role, $add=true)
    {

        foreach ($this->_roles as $roleObj)
        {
            if ($roleObj->is($role))
            {
                return $roleObj;
            }
        }
        if (($role instanceof Role\IRole) && $add)
        {
            $this->addRole($role);
            return $role;
        }
        return false;
    }
    /**
     *
     * $acl->hasRole('Admin');
     * $acl->hasRole('Member');
     *
     *
     * @param <type> $role
     * @return bool
     */
    public function hasRole($role)
    {
        if($this->getRole($role, false))
        {
            return true;
        }
        return false;
    }
    /**
     *
     * $acl->isAllowed('sunkan','games','action');
     * or
     * $user->setAcl($acl);
     * $user->hasAccess('games','section');
     *
     * @param <type> $role
     * @param <type> $section
     * @param <type> $action
     * @param <type> $object
     */
    public function isAllowed($role, $section=null, $action=null, $object=null)
    {
        $argsArray = array();
        $tmpArgs = func_get_args();
        $args[0] = $section;
        $args[1] = $action;
        $args[2] = $role;
        if(func_num_args() > 3)
        {
            for ($i = 4; $i < func_num_args(); $i++) {
                $args[$i] = func_get_arg($i);
            }
        }
        $tmpArgs = $args;
        $tmpArgs[0] = null;
        $tmpArgs[1] = null;

        if(!call_user_func_array(array($this,'_fireAssertion'), $tmpArgs))
        {
            return false;
        }
        if(!($roleObj = $this->getRole($role)))
        {
            return false;
        }
        $args[2] = $roleObj;
        $argsArray[] = $args;
        $args[1] = null;
        $argsArray[] = $args;
        $args[0] = null;
        $args[1] = $action;
        $argsArray[] = $args;

        foreach ($argsArray as $args)
        {
            if(!call_user_func_array(array($this, '_fireAssertion'), $args))
            {
                return false;
            }
        }
        /*
         * $thread = new Thread();
         * $acl->isAllowed('sunkan','forum','thread:edit', $thread);
         *
         */
        if ($object !== null && ($flag=$this->isOwner($roleObj, $object)))
        {
            $flag = $roleObj->isAllowed($section, $action, true);
            if ($flag)
            {
                return true;
            }
        }
        return $roleObj->isAllowed($section,$action);
    }
    public function isOwner($role, $object)
    {
        if(!($roleObj = $this->getRole($role)))
        {
            return false;
        }
        $flag = false;
        if ($object instanceof \yoLoo\Acl\Object\IObject)
        {
            $flag = $object->isOwner($roleObj);
        }
        elseif(is_object($object))
        {
            if (method_exists($object, 'isOwner'))
            {
                $flag = $object->isOwner($roleObj);
            }
            elseif (method_exists($object, 'hasRole'))
            {
                $flag = $object->hasRole($role);
            }
        }
        elseif(is_array($object))
        {
            $flag = ($object['owner']==$object['user']);
        }
        return $flag;
    }

}
