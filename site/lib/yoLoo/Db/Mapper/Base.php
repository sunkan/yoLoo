<?php
namespace yoLoo\Db\Mapper;

abstract class Base extends \yoLoo\Base
{
    protected $_yoLoo_Db_Mapper_Base = array(
        'cache' => '\\yoLoo\\Cache\\Adapter\\None'
    );

    protected $_registry = array(
        'dirty'   => array(),
        'old'     => array(),
        'new'     => array(),
        'objects' => array()
    );

    /**
     *
     * @var \yoLoo\Db\IConnection
     */
    protected static $_dbHandler = null;

    protected $_cache = null;

    protected $_relations = array();

    protected $_rowsetClass = '\\yoLoo\\Db\\Mapper\\Rowset';
    
    protected $_table = '';

    protected $_info = array();

    /**
     * Detta är våran Identity Map, den innehåller
     * alla objekt som har en motsvarande rad i databasen
     * varesig dem finns i $_dirtyObjects eller $_oldObjects
     *
     * @var mixed
     */
    protected $_persistedObjects = array();

    private $_stmt = null;

    public function errorInfo(){
        if ($this->_stmt!==null){
            return $this->_stmt->errorInfo();
        }
    }

    public function getTableName()
    {
        return $this->_info['tbale'];
    }
    public function getDbName()
    {
        return $this->_info['db'];
    }

    protected function _postConfig()
    {
        parent::_postConfig();
        if ($this->_cache === null)
        {
            if ($this->_config['cache'] instanceof \yoLoo\Cache\Adapter\Base)
            {
//echo '<!-- obj:m -->';
               $this->_cache = $this->_config['cache'];
            }
            elseif(is_string($this->_config['cache']))
            {

//echo '<!-- obj:s -->';

                $this->_cache = new $this->_config['cache']();
//echo '<!-- '.get_class($this->_cache).' -->';
            }
            else
            {
                $this->_cache = new \yoLoo\Cache\Adapter\None();
            }
        }

        $dbandtbl = \explode('.', $this->_table);
        if(\count($dbandtbl) > 1)
        {
            list($db,$tbl) = $dbandtbl;
        }
        else
        {
            $tbl = $dbandtbl[0];
            $db = '';
        }
        $this->_info['db'] = $db;
        $this->_info['table'] = $tbl;
    }

    public static function setDbHandler( \yoLoo\Db\IConnection $handler )
    {
        self::$_dbHandler = $handler;
    }

    public function setLimit( $limit )
    {
        $limit = (int)$limit;
        if( $limit < 1 )
        {
            $limit = 1;
        }

        $this->_limit = $limit;
    }
    public function getLimit()
    {
        return $this->_limit;
    }

    public function setPage( $page )
    {
        $page = (int)$page;
        if( $page < 1 )
        {
            $page = 1;
        }

        $this->_page = $page;
    }
    public function getPage()
    {
        return $this->_page;
    }

    protected function _execute( $sql, $params=array( ), array $options=array( ) )
    {
        $event = new \yoLoo\Event\Event($this, 'mapper.preExecute', array(
            'sql'=>$sql,
            'params'=>$params,
            'options'=>$options
        ));
        $this->_eventDispatcher->notify($event);
        $options['mapper'] = $this;
        $this->_stmt = $stmt = self::$_dbHandler->prepare ( $sql, $options );
        $rslt = $stmt->execute ( $params );
        

        $event = new \yoLoo\Event\Event($this, 'mapper.postExecute', array(
            'stmt'=>$stmt,
            'rs'=>$rslt
        ));
        $this->_eventDispatcher->notify($event);
        if ( $rslt )
        {
            switch ( substr ( strtolower($sql), 0, 6 ) )
            {
                case 'delete':
                case 'update':
                    return $stmt->rowCount();
                    break;
                case 'insert':
                    return $stmt->lastInsertId();
                    break;
                default :
                    return $stmt;
                    break;
            }
        }
        return false;
    }

    public function register( Object $obj, $type )
    {
        $type = \strtolower ($type);
        if( !\in_array ( $type, array('new', 'old', 'dirty') ) )
        {
            return false;
        }

        $this->_registry[$type] = $obj;
        return true;
    }
    abstract public function findById( $id );

    abstract protected function _getMapperObjectName();

    /**
     *
     * @param stdObject $data
     * @param mixed $key
     * @return Object
     */
    public function loadObject( $data, $key = false )
    {
        if ( $key === false )
        {
            $key = $data->id;
        }
        if (isset ($this->_persistedObjects[$key]) )
        {
            return $this->_persistedObjects[$key];
        }

        $objName = $this->_getMapperObjectName();
        $obj = new $objName ( $data, array('mapper' => $this) );
        $this->_persistedObjects[$key] = $obj;
        return $obj;
    }

    public function entry($key='')
    {
        //J-Mapper-Role-v1-134-v2
        $keyBase = sprintf('yoLoo/Mapper/%s/',$this->getMapperName(true));
       
        $mapperVersionKey = $keyBase.'version';
        $mapperVersion = (int)$this->_cache->fetch($mapperVersionKey);
       
        $dataVersionKey = sprintf($keyBase.'%d/%s/version',
                                        $mapperVersion, $key);
        $dataVersion = (int)$this->_cache->fetch($dataVersionKey);
       
        $dataKey = sprintf($keyBase.'%d/%s/%d/data',
                                $mapperVersion, $key, $dataVersion);
        return $dataKey;
    }
    public function updateVersion($key=null)
    {
        $keyBase = sprintf('yoLoo/Mapper/%s/',$this->getMapperName(true));

        $mapperVersionKey = $keyBase.'version';
        if($key === null)
        {
            return $this->_cache->increment($mapperVersionKey);
        }
        else
        {
            $mapperVersion = (int)$this->_cache->fetch($mapperVersionKey);
            $dataVersionKey = sprintf($keyBase.'%d/%s/version',
                                        $mapperVersion, $key);
            return $this->_cache->increment($dataVersionKey);
        }
    }

    protected function _fetchData($sql, $params, $options=array())
    {
        $cacheKey = $options['cache_key'];

        if (!($data = $this->_cache->fetch($cacheKey)))
        {
            $stmt = $this->_execute($sql, $params);
            if ($stmt !== false && $stmt !== null)
            {
                $stmt->setFetchMode(\PDO::FETCH_OBJ);

                $event = new \yoLoo\Event\Event($this, 'mapper.preFetchData', array(
                    'stmt'=>$stmt,
                ));
                $this->_eventDispatcher->notify($event);

                $data = $stmt->fetchAll();
                if (count($data)>0)
                {
                    $event = new \yoLoo\Event\Event($this, 'mapper.preCacheSave', array(
                        'cache'=>$this->_cache,
                        'data'=>$data,
                        'cache_key'=>$cacheKey
                    ));
                    $this->_eventDispatcher->notify($event);
                }
            }
            else
            {
                return false;
            }
        }

        $event = new \yoLoo\Event\Event($this, 'mapper.postFetchData', array(
            'data'=>$data,
        ));
        $this->_eventDispatcher->notify($event);
        return $event['data'];
    }
    public function fetchRawBySql($sql, array $params = array( ),
            $options = array( ) )
    {
        if(!isset ($options['cache_key']))
        {
            $options['cache_key'] = $this->entry(md5($sql. serialize($params)));
        }

        if(isset ($options['life']))
        {
            $this->_cache->setLife($options['life']);
        }

        return $this->_fetchData($sql, $params, $options);
    }
    /**
     *
     * @param string | \yoLoo\Sql $sql
     * @param array $params
     * @param array $options
     * @return Rowset | Object
     */
    public function findBySql( $sql, array $params = array( ),
            $options = array( ) )
    {
        if(!isset ($options['cache_key']))
        {
            $options['cache_key'] = $this->entry(md5($sql. serialize($params)));
        }

        if(isset ($options['life']))
        {
            $this->_cache->setLife($options['life']);
        }

        $data = $this->_fetchData($sql, $params, $options);

        $forceRowset = (bool)(isset($options['rowset'])?$options['rowset']:false);

        if(count($data)==1 && !$forceRowset)
        {
            return $this->loadObject($data[0]);
        }
        if(count($data)==0)
        {
            return null;
        }
        $config = (array)(isset($options['rowset_config'])?$options['rowset_config']:array());
        return $this->createRowset($data, $config);
    }
    public function createRowset($data, $config=array())
    {
        $rowset = $this->_rowsetClass;
        $config['mapper'] = $this;
        return new $rowset($data, $config);
    }

    /**
     * Sparar, uppdaterar och tar bort alla objekt som ändrats på något sätt
     *
     * @see Base::update()
     * @see Base::insert()
     * @see Base::delete()
     */
    public function commit()
    {
        foreach ( $this->_registry['new'] as $object )
        {
            $this->insert ( $object );
        }
        foreach ( $this->_registry['dirty'] as $object )
        {
            $this->update ( $object );
        }
        foreach ( $this->_registry['old'] as $object )
        {
            $this->delete ( $object );
        }
    }
    protected function _registryMove($obj, $from, $to)
    {
        if ($from=='new' && $to == 'persisted')
        {
            $this->_persistedObjects[$obj->getId()] = $obj;
        }
    }

    /**
     *
     * @param Object $obj
     */
    public function insert( $obj )
    {
        $event = new \yoLoo\Event\Event($this, 'mapper.preInsert', array(
            'obj'=>$obj,
        ));
        $this->_eventDispatcher->notify($event);

        $sql = 'INSERT INTO %s (%s) VALUES(%s)';
        if ($obj instanceof \yoLoo\Db\Mapper\Object)
        {
            $inData = $obj->getData();
        }
        elseif (is_array($obj) )
        {
            $inData = $obj;
        }

        foreach ($inData as $key=>$value)
        {
            if (is_array($value))
            {
                if (isset($value['type']) && $value['type']=='expr')
                {
                    $keys[] = $key;
                    $values[] = $value['value'];
                }
                else
                {
                    $keys[] = $key;
                    $values[] = '? ';
                    $data[] = $value['value'];
                }
            }
            else
            {
                $keys[] = $key;
                $values[] = '? ';
                $data[] = $value;
            }
        }
        $sql = sprintf($sql,$this->_table, implode(', ', $keys), implode(', ', $values));
        $id = $this->_execute($sql, $data);

        if ($id && $obj instanceof Object)
        {
            $obj->setId($id);
            $this->_registryMove($obj, 'new', 'persisted');
        }
        $event = new \yoLoo\Event\Event($this, 'mapper.postInsert', array(
            'obj'=>$obj,
        ));
        $this->_eventDispatcher->notify($event);
        return $id;

    }
    /**
     *
     * @param Object $obj
     */
    abstract public function delete( $obj );
    /**
     *
     * @param Object $obj
     */
    public function update($obj, $key=null)
    {
        $event = new \yoLoo\Event\Event($this, 'mapper.preUpdate', array(
            'obj'=>$obj,
        ));
        //var_dump(get_class($obj));
        $this->_eventDispatcher->notify($event);
        $sql = 'UPDATE %s SET %s WHERE id=? LIMIT 1';
        if ($obj instanceof \yoLoo\Db\Mapper\Object)
        {
            $inData = $obj->getData(true);
        }
        elseif (is_array($obj) && $key!==null)
        {
            $inData = $obj;
        }
        foreach ($inData as $key=>$value)
        {
            if (is_array($value))
            {
                if (isset($value['type']) && $value['type']=='expr')
                {
                    $cols .= $key.'='.$value['value'].',';
                }
                else
                {
                    $cols .= $key.'=?, ';
                    $data[] = $value['value'];
                }
            }
            else
            {
                $cols .= $key.'=?, ';
                $data[] = $value;
            }
        }
        $sql = sprintf($sql,$this->_table, trim($cols, ', '));
        if ($obj instanceof \yoLoo\Db\Mapper\Object)
        {
            $data[] = $obj->getId();
        }
        elseif (is_array($obj) && $key !== null)
        {
            $data[] = $key;
        }
        $rs = $this->_execute($sql, $data);
        $event = new \yoLoo\Event\Event($this, 'mapper.postInsert', array(
            'obj'=>$obj,
        ));
        $this->_eventDispatcher->notify($event);
        return $rs;
    }

    /**
     *
     * @return Object
     */
    public function fetchNewRow()
    {
        $rowName = $this->_getMapperObjectName();
        $row = new $rowName ( array(), array('mapper'=>$this) );

        return  $row;
    }

    public static function find()
    {
        $mapper = self::loadMapper(get_called_class());
        $args = func_get_args();
        if (isset($args[0]) && is_int($args[0]))
        {
            return $mapper->findById($args[0]);
        }
    }

    /**
     *
     * @param String $mapper
     * @return Base
     */
    public function load( $mapper = null )
    {
        return self::loadMapper( $mapper );
    }

    /**
     *
     * @var Base[]
     */
    protected static $_mappers = array( );
    
    /**
     *
     * @param <type> $onlyClassName
     * @return <type> 
     */
    function getMapperName($onlyClassName=false)
    {
        if ($onlyClassName)
        {
            $name = array_pop(explode('\\', get_class($this)));
            if ($name !== null)
            {
                return $name;
            }
        }
        return get_class($this);
    }

    /**
     *
     * @param String $mapper
     * @return Base
     */
    public static function loadMapper( $mapper = null )
    {
        if ( $mapper === null )
        {
            $mapper = get_called_class();
        }
        if ( isset ( self::$_mappers[$mapper] ) )
        {
            return self::$_mappers[$mapper];
        }

        return self::$_mappers[$mapper] = new $mapper(array());
    }

    public static function __callStatic($method,  $arguments)
    {
        if (strpos($method, 'find')===0)
        {
            $mapper = self::loadMapper(get_called_class());
            if (method_exists($mapper, $method))
            {
                return call_user_func_array(array($mapper, $method), $arguments);
            }
        }
    }

    protected function _buildWhere($where)
    {
        if (!is_array($where) || count($where)==0)
        {
            return array('1', array());
        }
        $sql = '';
        $params = array();
        foreach($where as $k){
            if (is_array($k)){
                list($q, $v) = $k;
                $sql .= ' '.$q .' AND';
                if (is_array($v))
                {
                    foreach ($v as $value) {
                        $params[] = $value;
                    }
                } else {
                    $params[] = $v;
                }

            }
            else
            {
                $sql .= ' '.$k.' AND';
            }
        }
        if (substr($sql, -4)==' AND')
        {
            $sql = substr($sql, 0, -4);
        }
        return array($sql, $params);
    }

}
