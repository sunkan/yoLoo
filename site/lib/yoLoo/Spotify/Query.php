<?php
namespace yoLoo\Spotify;

class Query extends \yoLoo\Base
{
    const LOOKUP = 1;
    const SEARCH = 2;

    protected $_yoLoo_Spotify_Query = array(
        'type'=>self::LOOKUP,
        'cache'=>'\\yoLoo\\Cache\\Adapter\\None'
    );

    protected $_result = null;
    protected $_type = self::LOOKUP;
    protected $_cache = null;

    protected function _postConfig()
    {
        if (\in_array($this->_config['type'], array(1,2))) {
            $this->_type = $this->_config['type'];
        }
        if (\is_string($this->_config['cache']))
        {
            $this->_cache = new $this->_config['cache']();
        }
        elseif($this->_config['cache'] instanceof \yoLoo\Cache\Adapter\Base)
        {
            $this->_cache = $this->_config['cache'];
        }

        if(!($this->_cache instanceof \yoLoo\Cache\Adapter\Base))
        {
            $this->_cache = new $this->_yoLoo_Spotify_Query['cache']();
        }
    }
    public function setType($type)
    {
        if (\in_array($type, array(1,2))) {
            $this->_type = $type;
        }
    }
    public function isType($type)
    {
        $keys['lookup'] = self::LOOKUP;
        $keys['search'] = self::SEARCH;

        return ($keys[$type]==$this->_type);
    }
    public function execute($query)
    {
        $query = $this->_buildQuery($query);
        if ($this->_type == self::LOOKUP)
        {
            $spotifyId = $query['q'];
            $url = 'http://ws.spotify.com/lookup/1/?uri='.$query['q'].$query['extra'];
            $key = md5($spotifyId);
        }
        if ($this->_type == self::SEARCH)
        {
            $format = 'http://ws.spotify.com/search/1/%s?q=%s';
            $url = sprintf($format, $query['type'],$query['q']);
            $key = md5($url);
        }
        if (!($data = $this->_cache->fetch($key)))
        {
            $content = file_get_contents($url);
            $data = simplexml_load_string($content);
            if (!$data)
            {
                return false;
            }
            $this->_cache->save($key, $data);
        }
        $this->_result = new Result($this, $data);
        return $this->_result;
    }
    protected $_query = array();
    public function getQuery($key=null)
    {
        if ($key === null)
        {
            return $this->_query;
        }
        if(isset($this->_query[$key]))
        {
            return $this->_query[$key];
        }
        return null;
    }
    protected function _buildQuery($q) {
        if($this->_type==self::LOOKUP)
        {
            if (stripos($q, 'http://open.spotify.com/')===0) {
                $str = str_ireplace('http://open.spotify.com/', '', $q);
                list($type, $id) = explode('/', $str);
            }
            elseif (stripos($q, 'spotify:')===0) {
                list($prot, $type, $id) = explode(':', $q);
            }
            $type = strtolower($type);

            if (!in_array($type, array('track','album','artist')) &&
                    !(strlen($id)>0)) {
                throw new Exception('must have type and id');
            }
            $parts = explode('&',$id,2);
            $extra = '';
            if(isset($parts[1]))
            {
                $id = $parts[0];
                $extra = '&'.$parts[1];
            }
            $query = sprintf('spotify:%s:%s',$type, $id);
            $this->_query = array('q'=>$query,'extra'=>$extra);
            return array('q'=>$query,'extra'=>$extra);
        }
        else
        {
            $parts = explode(':', $q,2);
            $query = array();
            if (isset($parts[1]) && in_array($parts[0],array('track','album','artist')))
            {
                $query['type'] = $parts[0];
                $query['q'] = $parts[1];
            }
            else
            {
                $query['type'] = 'track';
                $query['q'] = $q;
            }
            $this->_query = $query;
            return $query;
        }
    }
}