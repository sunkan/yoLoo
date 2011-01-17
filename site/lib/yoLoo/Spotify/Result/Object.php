<?php
namespace yoLoo\Spotify\Result;
class ObjectPart
{
    protected $_type = null;
    protected $_data = null;
    protected $_parent = null;
    protected $_allowedKeys = array('name','href','href_spotify','href_http');
    public function __construct($type, $data, $parent)
    {
        $this->_parent = $parent;
        $this->_type = $type;
        if ($data->getName()==$type)
        {
            $this->_data = $data;
        }
        elseif(isset($data->$type))
        {
            $this->_data = $data->$type;
        }
        else
        {
            $this->_data = array();
        }
        switch($type)
        {
            case 'track':
                $allowed = array('track_number','disc_number','length','popularity', 'album','artist');
                $this->_allowedKeys = array_merge($this->_allowedKeys, $allowed);
                break;
            case 'album':
                $allowed = array('released','artist','tracks');
                $this->_allowedKeys = array_merge($this->_allowedKeys, $allowed);
                break;
        }
    }
    public function isAvailable($code)
    {
        if ($this->_type == 'album')
        {
            if (mb_stripos((string)$this->_data->availability->territories, $code)===false)
                return false;
        }
        if ($this->_type == 'track')
        {
            return $this->album->isAvailable($code);
        }
        return true;
    }
    protected $_tracks = null;
    public function __get($key)
    {
        if (in_array($key, $this->_allowedKeys))
        {
            if(stripos($key, 'href')===0)
            {
                $href = $this->_data['href'];
                if($key=='href_http')
                {
                    $format = 'http://open.spotify.com/%s/%s';
                    list($prot, $type, $id) = explode(':', $href);
                    $href = sprintf($format, $type, $id);
                }
                return ($href);
            }
            if ($key=='tracks')
            {
                if($this->_tracks===null)
                {
                    $q = $this->_parent->getQuery();
                    $q->setType(\yoLoo\Spotify\Query::SEARCH);
                    $rs = new \yoLoo\Spotify\Result($q, $this->_data->tracks->children());
                    $this->_tracks = $rs->fetchAll();
                }
                return $this->_tracks;
            }
            if (in_array($key, array('artist','album')))
            {
                return $this->_parent->$key;
            }
            if ($key == 'track_number')
            {
                $key = 'track-number';
            }
            if ($key == 'disc_number')
            {
                $key = 'disc-number';
            }
            if (isset($this->_data->$key))
            {
                return ((string)$this->_data->$key);
            }
        }
    }
}
class Object extends \yoLoo\Base
{
    protected $_data = null;
    protected $_query = null;
    public function __construct($data,$query)
    {
        $this->_data = $data;
        $this->_query = $query;
    }
    public function getQuery()
    {
        return $this->_query->getQuery();
    }
    protected $_loaded = array();
    public function __get($key)
    {
        if(in_array($key, array('track','album','artist')))
        {
            if (!isset($this->_loaded[$key]))
            {
                $this->_loaded[$key] = new ObjectPart($key, $this->_data, $this);
            }
            return $this->_loaded[$key];
        }
    }
    public function isAvailable($code)
    {
        return $this->album->isAvailable($code);
    }
}