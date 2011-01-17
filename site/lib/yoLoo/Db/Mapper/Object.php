<?php
namespace yoLoo\Db\Mapper;

abstract class Object extends \yoLoo\Base 
{
    protected $_mapper = null;
    protected $_data = array();
    protected $_dirtyKeys = array();
    protected $_relations = array();
    
    public function __construct($data, $config=array()) 
    {
        $this->setData((array)$data);
        $this->_data['orginal'] = (array)$data;
        $this->_data['relations'] = array();
        if(isset ($config['mapper']))
        {
            $this->setMapper($config['mapper']);
        }
        parent::__construct($config);
    }
    public function getMapper()
    {
        return $this->_mapper;
    }
    public function setMapper(\yoLoo\Db\Mapper\Base $mapper)
    {
        $this->_mapper = $mapper;
        if (count($this->_dirtyKeys)>0)
        {
            $this->_mapper->register($this, 'dirty');
        }
    }
    public function setId($id)
    {
        if (isset ($this->_data['orginal']['id']))
        {
//echo 'neger';
            return false;
        }

        $this->_data['orginal']['id'] = $id;
        $this->_data['dirty']['id'] = $id;
    }
    public function getId()
    {
        return $this->_data['orginal']['id'];
    }
    public function getObjectKey()
    {
        return $this->getId();
    }
    public function getRevisionKey()
    {
        return ''.$this->getId();
    }

    public function  __set($name,  $value)
    {
        if($name == 'id')
            return false;

        if ($this->_data['orginal'][$name]!=$value)
        {
            $this->_dirtyKeys[] = $name;
            $this->_data['dirty'][$name] = $value;
            if ($this->getId()!==null && $this->_mapper instanceof \yoLoo\Db\Mapper\Base)
            {
                $this->_mapper->register($this,'dirty');
            }
        }
        return true;
    }
    
    public function __isset($name)
    {
        if(isset ($this->_data['dirty'][$name]))
        {
            return true;
        }

        if (isset ($this->_relations[$name]))
        {
            return $this->getRelation($name)!==null;
        }

        return false;
    }

    public function __get($name) 
    {
        $method = 'get'.ucfirst($name);
        if (method_exists($this, $method))
        {
            return $this->$method();
        }
        if(isset ($this->_data['dirty'][$name]))
        {
            return $this->_data['dirty'][$name];
        }

        if (isset ($this->_relations[$name]))
        {
            return $this->getRelation($name);
        }

        return null;
    }

    public function getRelation($rel)
    {
        if (isset ($this->_data['relations'][$rel]))
        {
            return $this->_data['relations'][$rel];
        }
        $relation = $this->_relations[$rel];
        $mapper = $relation['mapper'];
        $data = null;
        if (is_string($mapper))
        {
            $mapper = $this->_mapper->load($mapper);
        }

        if (isset($relation['foreign_key']))
        {
            $key = $relation['foreign_key'];
            if (isset($this->_data['dirty'][$key]))
            {
                if (isset($relation['method']))
                {
                    $method = $relation['method'];
                }
                else
                {
                    return null;
                }
                if (method_exists($mapper, $method))
                {
                    $data = $mapper->$method($this->_data['dirty'][$key]);
                }
            }
        }
        else
        {
            $class = strtolower(substr(get_class($this),strrpos(get_class($this),'\\')+1));

            if (isset($relation['method']))
            {
                $method = $relation['method'];
            }
            else
            {
                $method = 'findBy'.ucfirst($class);
            }
            if (method_exists($mapper, $method))
            {
                $data = $mapper->$method($this);
            }
            else
            {
                $where = array('sql'=>$class.'_id = ?',
                               'param'=>array($this->getId()));
                $data = $mapper->findAll($where);
            }
        }
#echo 'hej skoj';
        $event = new \yoLoo\Event\Event($this, 'mapper.postRelationFetch', array(
            'relation'=>$rel,
            'data'=>$data
        ));
        $this->_eventDispatcher->notify($event);
        $data = $event['data'];
        if (($data instanceof Rowset) || ($data instanceof Object))
        {
            $this->_data['relations'][$rel] = $data;
        }
        return $this->_data['relations'][$rel];
    }
    
    public function delete()
    {
        return (bool)$this->_mapper->delete($this);
    }
    public function save()
    {
        if ($this->getId()==null)
        {
            return $this->_mapper->insert($this);
        }
        if (count($this->_dirtyKeys)>0)
        {
            return (bool)$this->_mapper->update($this);
        }
        return true;
    }
    public function get($keys,$dataType='orginal')
    {
        if (!in_array($dataType,array('orginal','dirty')))
        {
            $dataType = 'orginal';
        }
        if (is_array($keys))
        {
            $return  = array();
            foreach ($keys as $key)
            {
                $return[$key] = $this->_data[$dataType][$key];
            }
            return $return;
        }
        elseif(is_string($keys))
        {
            if (isset ($this->_data[$dataType][$keys]))
            {
                return $this->_data[$dataType][$keys];
            }
        }
        return null;

    }
    public function setData(array $data)
    {
        $this->_data['dirty'] = $data;
    }
    public function getData($onlyDirty=false)
    {
        if ($onlyDirty===true)
        {
            return  $this->get($this->_dirtyKeys,'dirty');
        }
        if ($onlyDirty=='orginal')
        {
            return $this->_data['orginal'];
        }
        return $this->_data['dirty'];
    }
    public function isDirty()
    {
        return count($this->_dirtyKeys);
    }
}
