<?php
namespace yoLoo\Auth\Adapter;


class Mapper extends \yoLoo\Base implements \yoLoo\Auth\Adapter\IAdapter
{
    /**
     *
     * @var \yoLoo\Db\Mapper\Base
     */
    protected $_mapper = null;
    /**
     *
     * @var string
     */
    protected $_username = null;
    /**
     *
     * @var string
     */
    protected $_password = null;

    protected $_state = null;

    protected $_user = null;
    protected $_fail_user = null;

    protected $_yoLoo_Auth_Adapter_Mapper = array(
        'mapper' => null,
        'session_key'=>'yoLoo_Auth_Adapter_Mapper'
    );

    /**
     *
     * @var string
     */
    private $_session_key = 'yoLoo_Auth_Adapter_Mapper';

    public function  __construct($config=array())
    {
        parent::__construct($config);
        if(isset($_SESSION[$this->_session_key]) &&
           $_SESSION[$this->_session_key]['auth']==true)
        {
            $this->_username = $_SESSION[$this->_session_key]['username'];
            $this->_password = $_SESSION[$this->_session_key]['password'];
        }
    }
    protected function _postConfig()
    {
        if (isset($this->_config['mapper']))
        {
            $this->setMapper($this->_config['mapper']);
        }
        $this->_session_key = $this->_config['session_key'];
    }

    /**
     *
     * @param \yoLoo\Db\Mapper\Base $mapper
     * @return Mapper
     */
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;

        return $this;
    }
    public function getMapper()
    {
        if (is_string($this->_mapper))
        {
            return \yoLoo\Db\Mapper\Base::loadMapper($this->_mapper);
        }
        return $this->_mapper;
    }

    /**
     *
     * @param string $user
     * @return Mapper
     */
    public function setUsername($user)
    {
        $this->_username = $user;

        return $this;
    }
    /**
     *
     * @param <type> $password
     * @return Mapper
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getFailUser()
    {
        return $this->_fail_user;
    }

    /**
     *
     * @return \yoLoo\Auth\IUser or false
     */
    public function authenticat()
    {
        if (empty ($this->_username) || $this->_username=='')
        {
            return \yoLoo\Auth\Auth::FAIL;
        }
        $user = $this->getMapper()->findByUsername($this->_username);
        if (!$user || !($user instanceof \yoLoo\Auth\IUser))
        {
            return \yoLoo\Auth\Auth::FAIL;
        }
        $state = $user->checkAuth($this->_password);
        if($state==\yoLoo\Auth\Auth::SUCCESS)
        {
            $_SESSION[$this->_session_key] = array(
                'auth'=>true,
                'username'=>$user->getUsername(),
                'password'=>$this->_password
            );
            $this->_user = $user;
        }
        else
        {
            $this->_fail_user = $user;
        }
        return $state;
    }

    /**
     * 
     */
    public function destroy()
    {
        session_unset();
        session_destroy();
    }
}
