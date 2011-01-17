<?php
namespace yoLoo;
class Auth extends Base
{
    const SUCCESS = 1;
    const FAIL = 2;

    /**
     * @var Auth\User
     */
    protected $_user = null;
    protected $_hasAuth = null;
    /**
     * @var Auth\Adapter
     */
    protected $_adapter = null;

    public function setAdapter(Auth\Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    public function getAdapter()
    {
        return $this->_adapter;
    }

    public function authenticat(Auth\Adapter $adapter=null)
    {
        if($apapter !== null)
        {
            $this->setAdapter($adapter);
        }
        $this->_hasAuth = self::FAIL;

        $event = new \yoLoo\Event\Event($this, 'auth.preAuthenticate', array(
            'adapter'=>$this->_adapter
        ));
        $this->_eventDispatcher->notify($event);

        $user = $this->_adapter->authenticat();

        if ($user instanceof \yoLoo\Auth\IUser)
        {
            $this->_hasAuth = self::SUCCESS;
            $this->_user = $user;
        }

        $event = new \yoLoo\Event\Event($this, 'auth.postAuthenticate', array(
            'has_auth'=>$this->_hasAuth,
            'user'=>$user
        ));
        $this->_eventDispatcher->notify($event);

        return (self::SUCCESS == $this->_hasAuth);
    }
    public function hasAuth()
    {
        if($this->_hasAuth === null)
        {
            return $this->authenticat();
        }
        return (self::SUCCESS==$this->_hasAuth);
    }

    /**
     * @return Auth\User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     *
     * @param Auth\User $user
     */
    public function setUser(Auth\User $user)
    {
        $this->_hasAuth = null;
        $this->_user = $user;
    }
}