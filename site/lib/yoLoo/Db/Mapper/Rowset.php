<?php
namespace yoLoo\Db\Mapper;

class Rowset extends \yoLoo\Collection\ACollection implements IRowset
{
    protected $_mapper = null;
    public function loadIndex($index, $conf=array())
    {
        if (empty($this->_rows[$index]))
        {
            if (is_array($conf['keys']))
            {
                $key = '';
                foreach ($conf['keys'] as $keys)
                {
                    $key .= md5($this->_data[$index]->$keys);
                }
                $key = md5($key);
            }
            else
            {
                $key = $this->_data[$index]->id;
            }
            
            return $this->_mapper->loadObject($this->_data[$index], $key);
        }
        return $this->_rows[$index];
    }
    public function getRow($pos)
    {
        $key = $this->key();
        try
        {
            $this->seek($position);
            $row = $this->current();
        }
        catch (\yoLoo\Collection\Exception\OutOfBounds $e)
        {
            throw new \yoLoo\Db\Mapper\Exception('No row could be found at position ' . (int) $position);
        }
        $this->seek($key);

        return $row;
    }

    public function setMapper(Base $mapper)
    {
        $this->_mapper = $mapper;
    }
    public function getMapper()
    {
        return $this->_mapper;
    }

    public function delete()
    {
        foreach ($this as $row)
            $row->delete();
    }
    public function save()
    {
        foreach ($this as $row)
            $row->save();
    }

    protected $_pager = null;
    public function getPager()
    {
        return $this->_pager;
    }
    public function setPager(\yoLoo\Pager\IPager $pager)
    {
        $this->_pager = $pager;

        return $this;
    }
    protected function _postConfig() 
    {
        parent::_postConfig();
        $this->_pager = new \yoLoo\Pager();
        $this->_mapper = $this->_config['mapper'];
    }

    public function setPagerInfo(array $data)
    {
        $keys = array('pages', 'current');
        foreach ($keys as $key)
        {
            if (!isset($data[$key]))
            {
                if ($key == 'current')
                {
                    $data['current'] = $this->_mapper->getPage();
                }
                if ($key == 'pages')
                {
                    $data['pages'] = $this->_mapper->countAll(true);
                }
            }
        }
        $this->_pager->setInfo($data);
    }
    public function getPagerInfo()
    {
        return $this->_pager->getInfo();
    }
}
