<?php
namespace yoLoo\Auth;

/**
 *
 * 
 * @author Andreas "Sunkan" Sundqvist <yoLoo@sunkan.se>
 */
class Auth extends \yoLoo\Base
{
    const SUCCESS = 1;
    const FAIL = 2;

    /**
     * @var Auth\IUser
     */
    protected $_user = null;
    /**
     *
     * @var bool
     */
    protected $_state = null;
    /**
     * @var Auth\Adapter\IAdapter
     */
    protected $_adapter = null;

    public function setAdapter(Adapter\IAdapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    public function getAdapter()
    {
        return $this->_adapter;
    }

    public function authenticat(Adapter\IAdapter $adapter=null)
    {
        if($apapter !== null)
        {
            $this->setAdapter($adapter);
        }
        $this->_state = self::FAIL;

        $event = new \yoLoo\Event\Event($this, 'auth.preAuthenticate', array(
            'adapter'=>$this->_adapter,
            'fail'=>false,
            'fail_code'=>self::FAIL
        ));
        $this->_eventDispatcher->notify($event);

        if ($event['fail'])
        {
            $this->_state = $event['fail_code'];
            return $event['fail_code'];
        }
        $this->_state = $this->_adapter->authenticat();

        if ($this->_state == self::SUCCESS)
        {
            $this->_user = $this->_adapter->getUser();
        }

        $event = new \yoLoo\Event\Event($this, 'auth.postAuthenticate', array(
            'state'=>$this->_state,
            'user'=>$this->_user
        ));
        $this->_eventDispatcher->notify($event);
        
        $this->_user = $event['user'];
        $this->_state = $event['state'];

        return (self::SUCCESS == $this->_state);
    }
    public function hasAuth()
    {
        if($this->_state === null)
        {
            return $this->authenticat();
        }
        return (self::SUCCESS==$this->_state);
    }

    /**
     * @return Auth\User
     */
    public function getUser()
    {
        return $this->_user;
    }
    public function getFailId(){
        return $this->_adapter->getFailUser()->getId();
    }
    public function getState()
    {
        return $this->_state;
    }

    /**
     *
     * @param Auth\User $user
     */
    public function setUser(IUser $user)
    {
        $this->_state = null;
        $this->_user = $user;
    }
}
