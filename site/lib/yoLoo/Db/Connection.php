<?php
namespace yoLoo\Db;

class Connection extends \yoLoo\Base implements IConnection
{
    protected $_yoLoo_Db_Connection = array(
        'options'=>array(),
        'profiler'=>'\\yoLoo\\Db\\Profiler\\None'
    );

    protected $_nameClosing = '';
    protected $_nameOpening = '';

    /**
     *
     * @var PDO
     */
    protected $_pdo = null;

    /**
     *
     * @var Profiler\IProfiler
     */
    protected $_profiler = null;

    /**
     *
     * @return Profiler\IProfiler
     */
    public function getProfiler()
    {
        return $this->_profiler;
    }

    /**
     *
     * @param IProfiler $profiler
     * @return Connection
     */
    public function setProfiler(Profiler\IProfiler $profiler)
    {
        $this->_profiler = $profiler;

        return $this;
    }

    /**
     *
     *
     * @see yoLoo\Base::_postConstruct()
     */
    protected function _postConstruct()
    {
        parent::_postConstruct();
        $profiler = $this->_config['profiler'];
        if (is_string($profiler))
        {
            $profiler = new $profiler();
        }
        $this->setProfiler($profiler);
        if ( $thia->_config['connect'] )
        {
            $this->connect();
        }
    }

    /**
     *
     * @param string $table
     * @return \yoLoo\Sql\Query
     */
    public function getQuery($table='')
    {
        $query = new \yoLoo\Sql\Query($table);
        $query->setQuoteCallback(array($this, '_quote'));

        return $query;
    }

    /**
     *
     * @param string $type
     * @param string $value
     * @return string
     */
    public function _quote($type, $value)
    {
        if (in_array($type, array('name','field')))
        {
            return $this->quoteName($value);
        }
        return $this->_pdo->quote($value);
    }

    /**
     *
     * @return bool
     */
    public function isConnected()
    {
        try
        {
            if ( $this->_pdo === null )
            {
                return false;
            }
            $this->_pdo->query('SELECT 1');
            return true;
        } 
        catch ( PDOException $e )
        {
            $this->connect();  // Don't catch exception here, so that 
                               // re-connect fail will throw exception
        }

        return ($this->_pdo instanceof \PDO);
    }

    /**
     *
     * @return bool
     */
    public function connect()
    {
        if (($this->_pdo instanceof PDO))
        {
            return true;
        }
        $config = $this->_config;
        $event = new \yoLoo\Event\Event($this, 'db.preConnect', array(
            'config'=>$config
        ));
        $this->_eventDispatcher->notify($event);
        $this->_pdo = new \PDO($config['dns'],
                               $config['username'],
                               $config['password'],
                               $config['options']);

        $flag = ($this->_pdo instanceof PDO);
        if ($flag)
        {
            $this->_pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
            if (stripos($config['dns'], 'mysql:')===0)
            {
                $this->_pdo->setAttribute( \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            }

            $this->_nameOpening = $this->_nameClosing = '`';
        }

//        $event = new \yoLoo\Event\Event($this, 'db.preConnect', array(
  //          'pdo'=>$this->_pdo,
    //        'flag'=>$flag
      //  ));
        //$this->_eventDispatcher->notify($event);
        return $flag;
    }

    /**
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name,  $arguments)
    {
        if (!$this->isConnected())
        {
            $this->connect();
        }

        if(\method_exists($this->_pdo, $name))
        {
            return \call_user_func_array(array($this->_pdo, $name), $arguments);
        }

        return false;
    }

    /**
     *
     * @param string $sql
     * @param array $options
     * @return \PDOStatement
     */
    public function prepare($sql, $options=array())
    {
        $sql = (string)$sql;
        if (!$this->isConnected())
        {
            $this->connect();
        }
        if(!\key_exists(\PDO::ATTR_STATEMENT_CLASS, $options))
        {
            $options[\PDO::ATTR_STATEMENT_CLASS] = array('yoLoo\Db\Statement',
                array($this, $options)
            );
        }

        $event = new \yoLoo\Event\Event($this, 'db.prePrepareStatment', array(
            'sql'=>$sql,
            'options'=>$options
        ));
        $this->_eventDispatcher->notify($event);

        $stmt = $this->_pdo->prepare($sql, $options);

        $event = new \yoLoo\Event\Event($this, 'db.postPrepareStatment', array(
            'stmt'=>$stmt
        ));
        $this->_eventDispatcher->notify($event);

        return $stmt;
    }

    public function exec($statement,$options = array())
    {
        $event = new \yoLoo\Event\Event($this, 'db.preExecute', array(
            'sql'=>$this->queryString,
            'params'=>array(),
            'options'=>$options,
            'connection'=>$this
        ));
        $this->_eventDispatcher->notify($event);

        $rs = $this->_pdo->exec($statement);

        $event = new \yoLoo\Event\Event($this, 'db.postExecute', array(
            'sql'=>$this->queryString,
            'params'=>array(),
            'options'=>$options,
            'connection'=>$this,
            'rslt'=>$rs
        ));
        $this->_eventDispatcher->notify($event);

        return $rs;
    }

    /**
     *
     * @param string $name
     * @return string
     */
    public function quoteName($name)
    {
        $names = array();
        foreach (explode(".", $name) as $name)
        {
            $q = str_replace($this->_nameClosing, $this->_nameClosing.$this->_nameClosing, $name);
            $names[] = $this->_nameOpening . $q . $this->_nameClosing;
        }
        return implode(".", $names);
    }


}
