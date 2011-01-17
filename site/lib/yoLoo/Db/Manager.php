<?php
namespace yoLoo\Db;


class Manager extends \yoLoo\Base implements IConnection
{
    /**
     *
     *
     * 'pool' => array(
     *      'slave' => array(
     *          'adapter' => 'mysql',
     *          'host' => 'slave.host.com',
     *          'username' => 'slave',
     *          'password' => 'slavePass',
     *          'db' => 'test_db'
     *      ),
     *      'forum.master' => array(
     *          'adapter' => 'mysql',
     *          'host' => 'master2.host.com',
     *          'username' => 'master',
     *          'password' => 'masterPass',
     *          'db' => 'test_db2',
     *          'type' => 'master'
     *      )
     * )
     * or
     * 'pool' => array(
     *      'slave' => array(
     *          'connection' => new Connection($conf);
     *          'db' => 'test_db'
     *      ),
     *      'forum.master' => array(
     *          'connection' => new Connection($conf);
     *          'db' => 'test_db2',
     *          'type' => 'master'
     *      )
     * )
     *
     * @var <type>
     */
    protected $_yoLoo_Db_Manager = array(
        'connectionClass' => '\\yoLoo\\Db\\Connection',
        'profiler'  => '\\yoLoo\\Db\\Profiler\\None',
        'cache'      => array('\\yoLoo\\Cache\\Adapter\\None'),
        'dns'        => null,
        'connection' => null,
        'username'   => null,
        'password'   => null,
        'options'    => array(),
        'pool'       => array(),
        'deafult'    => 'master'
        );

    /**
     *
     * @var array
     */
    protected $_connectionList = array();
    /**
     *
     * @var <type>
     */
    protected $_connectionAliases = array();
    /**
     *
     * @var <type>
     */
    protected $_connectionInfo = array();
    /**
     *
     * @var <type>
     */
    protected $_activeConnection = null;
    /**
     *
     * @var <type>
     */
    protected $_dbToConnection = array();

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
     * @return Manager
     */
    public function setProfiler(Profiler\IProfiler $profiler)
    {
        $this->_profiler = $profiler;
        if ( count($this->_connectionList)>0)
        {
            foreach ( $this->_connectionList as $conn )
            {
                $conn->setProfiler($profiler);
            }
        }
        return $this;
    }

    protected function _postConstruct()
    {
        parent::_postConstruct();
        $profiler = $this->_config['profiler'];
        if (is_string($profiler))
        {
            $profiler = new $profiler();
        }
        $this->setProfiler($profiler);
        if(isset ($this->_config['connection']) &&
           $this->_config['connection'] instanceof IConnection)
        {
            $connection = $this->_config['connection'];
            $connection->setProfiler($this->getProfiler());
        }
        elseif($this->_config['username']!==null)
        {
            if(isset($this->_config['dns']))
                $dns = $this->_config['dns'];
            else
            {
                $dnsFormat = '%s:host=%s;dbname=%s;port=%d;';
                $type = (isset($this->_config['type'])?$this->_config['type']:'mysql');
                $host = (isset($this->_config['host'])?$this->_config['host']:'localhost');
                $port = (int)(isset($this->_config['port'])?$this->_config['port']:3306);
                $db   = (isset($this->_config['db'])?$this->_config['db']:'test');
                $dns = \sprintf($dnsFormat, $type, $host, $db, $port);
            }
            $config['dns'] = $dns;
            $config['username'] = $pool['username'];
            $config['password'] = $pool['password'];
            $config['profiler'] = $this->getProfiler();

            $connection = new $this->_config['connectionClass']($config);
        }
        if ($connection instanceof IConnection)
        {
            $this->addConnection('master', 'master', $connection, null);
        }

        if (count($this->_config['pool']))
        {
            foreach ($this->_config['pool'] as $key => $pool)
            {
                $config = array();
                if (isset ($pool['connection']) &&
                   $pool['connection'] instanceof IConnection)
                {
                    $connection = $pool['connection'];
                    $connection->setProfiler($this->getProfiler());
                }
                else
                {
                    if(isset($pool['dns']))
                        $dns = $pool['dns'];
                    else
                    {
                        $dnsFormat = '%s:host=%s;dbname=%s;port=%d;';
                        $type = (isset($pool['adapter'])?$pool['adapter']:'mysql');
                        $host = (isset($pool['host'])?$pool['host']:'localhost');
                        $port = (int)(isset($pool['port'])?$pool['port']:3306);
                        $db   = (isset($pool['db'])?$pool['db']:'test');
                        $dns = \sprintf($dnsFormat, $type, $host, $db, $port);
                    }
                    $config['dns'] = $dns;
                    $config['username'] = $pool['username'];
                    $config['password'] = $pool['password'];
                    $config['profiler'] = $this->getProfiler();

                    $connection = new $this->_config['connectionClass']($config);
                }
                $type = (isset($pool['type'])?$pool['type']:'slave');
                $this->addConnection($type, $key, $connection, $pool['db']);
            }
        }

        if (count($this->_config['database']))
        {
            foreach ($this->_config['database'] as $db=>$servers)
            {
                if(isset($servers['master']))
                {
                    $this->registerDbToConnection($db, $servers['master'], 'master');
                }
                if(isset($servers['slave']))
                {
                    $this->registerDbToConnection($db, $servers['slave'], 'slave');
                }
            }
        }
        parent::_postConstruct();
    }

    /**
     *
     * @param string $type
     * @param string $name
     * @param IConnection $connection
     * @param string $db
     */
    public function addConnection($type, $name, IConnection $connection, $db=null)
    {
        $this->_connectionList[$name] = $connection;
        $this->_connectionInfo[$name] = array(
            'type' => $type,
        );
        if($db!==null)
        {
            $this->registerDbToConnection($db, $name, $type);
        }
    }

    /**
     *
     * $manager->addConnection('master','server1', new Connection());
     * $manager->registerAlias('server2','server1','slave');
     *
     * @param string $alias
     * @param string $connection
     * @param string $type
     * @param string $db
     */
    public function registerAlias($alias, $connection, $type, $db=null)
    {
        if(isset ($this->_connectionList[$alias]))
        {
            throw new \yoLoo\Db\Exception('A connection by that name alredy exist.');
        }
        if(isset ($this->_connectionList[$connection]))
        {
            $this->_connectionAliases[$alias] = $connection;
            $this->_connectionInfo[$alias] = array(
                'type' => $type,
            );
        }

        if($db!==null)
        {
            $this->registerDbToConnection($db, $connection, $type);
        }
    }

    /**
     *
     * @param string $db
     * @param string $connection
     * @param string $type
     */
    public function registerDbToConnection($db, $connection, $type='master')
    {
        $this->_dbToConnection[$db][$type] = array('type'=>$type,
                                            'connection'=>$connection);
    }

    /**
     *
     * @param string $table
     * @param string $conn
     * @return \yoLoo\Sql\Query
     */
    public function getQuery($table='', $conn='default')
    {
        if ($this->_activeConnection === null || $conn != 'default')
        {
            $this->useConnection($conn);
        }
        return $this->_activeConnection->getQuery($table);
    }

    /**
     *
     * @param string $name
     * @return IConnection
     */
    private function _getConnection($name)
    {
        if (isset ($this->_connectionAliases[$name]))
        {
            $name = $this->_connectionAliases[$name];
        }

        if (isset ($this->_connectionList[$name]))
        {
            return $this->_connectionList[$name];
        }
        return null;
    }

    /**
     *
     * @param string $name
     * @return IConnection
     */
    public function getConnection($name = null)
    {
        if ($conn === null)
        {
            return $this->_activeConnection;
        }
        return $this->_getConnection($name);
    }

    /**
     *
     * @param string $db
     * @param string $type
     * @return IConnection
     */
    private function _getConnectionByDb($db, $type)
    {
        if (isset ($this->_dbToConnection[$db][$type]))
        {
            $connName = $this->_dbToConnection[$db][$type]['connection'];
            return $this->_getConnection($connName);
        }
        if($type=='slave')
        {
            return $this->_getConnectionByDb($db, 'master');
        }
        return null;
    }

    /**
     *
     * @param string $type
     * @param string $db
     * @return bool;
     */
    public function useConnection($type, $db=null)
    {
        $conn = null;
        if($db!==null && isset ($this->_dbToConnection[$db]))
        {
            $conn = $this->_getConnectionByDb($db, $type);
            if($conn === null)
            {
                $type = $this->_config['default'];
            }
        }

        if ($conn === null)
        {
            $conn = $this->_getConnection($this->_config['default']);
        }

        if ($conn === $this->_activeConnection)
        {
            return true;
        }

        if(!$conn->isConnected())
        {
            $conn->connect();
        }

        $this->_activeConnection = $conn;
        return true;
    }

    /**
     *
     * @param string $sql
     * @param array $options
     * @return \PDOStatement
     */
    public function prepare($sql, $options=array())
    {
        $sql = \strtolower(\trim((string)$sql));
        $type = \trim(\substr($sql, 0,\stripos($sql, ' ')));
        if(isset ($options['connection']))
        {
            $this->useConnection($options['connection']);
        }
        else
        {
            switch ($type)
            {
                case 'select' :
                case 'delete' :
                    $sqlTmp = \trim(\substr($sql,\stripos($sql, ' from ')+6));
                    $e = \stripos($sqlTmp, ' ');
                    if( \stripos($sqlTmp, ' ')===false)
                    {
                        $e = \strlen($sqlTmp);
                    }

                    $dbandtbl = \substr($sqlTmp, 0, $e);
                    break;
                case 'update' :
                    list($dbandtbl,$r) = \array_map('trim', \explode(' ',\trim(\substr($sql, 7)),2));
                    break;
                case 'insert' :
                    list($dbandtbl,$r) = \array_map('trim', \explode(' ',\trim(\substr($sql, 12)),2));
                    break;
                case 'use':
                    $dbandtbl = strtolower(trim(substr($sql, 3)));
                    $dbandtbl .= '.test';
                    break;
                default:
                    $dbandtbl = null;
                    break;
            }
            $dbandtbl = \explode('.', $dbandtbl);
            if(\count($dbandtbl) > 1)
            {
                list($db, $tbl) = $dbandtbl;
            }
            else
            {
                $tbl = $dbandtbl[0];
                $db = null;
            }
            if($type == 'select')
            {
                $this->useConnection('slave',$db);
            }
            else
            {
                $this->useConnection('master',$db);
            }
        }
        return $this->_activeConnection->prepare($sql,$options);
    }

    /**
     *
     * @param string $query
     * @param array $options
     * @return int
     */
    public function exec($query, $options=array())
    {
        if(isset ($options['connection']))
        {
            $this->useConnection($options['connection']);
        }
        return $this->_activeConnection->exec((string)$query);
    }

    /**
     *
     * @param string $conn
     * @return bool
     */
    public function isConnected($conn = 'default')
    {
        if ($this->_activeConnection === null || $conn != 'default')
        {
            $this->useConnection($conn);
        }
        return $this->_activeConnection->isConnected();
    }

    /**
     *
     * @param string $conn
     * @return bool
     */
    public function connect($conn = 'default')
    {
        if ($this->_activeConnection === null || $conn != 'default')
        {
            $this->useConnection($conn);
        }
        return $this->_activeConnection->isConnected();
    }

    /**
     *
     * @param string $str
     * @return string
     */
    public function quote($str)
    {
        return $this->_activeConnection->quote($str);
    }

    /**
     *
     * @param string $str
     * @return string
     */
    public function quoteName($str)
    {
        return $this->_activeConnection->quoteName($str);
    }

    /**
     *
     * @param int $attribute
     * @param mixed $value
     * @param array $options
     * @return bool
     */
    public function setAttribute($attribute, $value, $options =array())
    {
        if(isset ($options['connection']))
        {
            $this->useConnection($options['connection']);
        }

        return $this->_activeConnection->setAttribute($attribute, $value);
    }

    /**
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->_activeConnection->lastInsertId();
    }
    public function errorInfo()
    {
        return $this->_activeConnection->errorInfo();
    }
}
