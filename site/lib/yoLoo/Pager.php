<?php
namespace yoLoo;

class Pager extends Base implements Pager\IPager
{
    protected $_file = null;
    protected $_renderd = array();

    protected $_info = array();
    protected $_urlCallback = null;

    protected function _postConfig()
    {
        if($this->_urlCallback === null)
        {
            $this->_urlCallback = function(){};
        }
    }
    public function getNrOfItems()
    {
        return $this->_info['items'];
    }
    public function hasPages()
    {
        return $this->getNrOfPages()>1;
    }
    public function setInfo(array $data)
    {
        if (isset($data['url_callback']))
        {
            $this->setUrlCallback($data['url_callback']);
        }
        $this->_info = $data;
    }
    public function getInfo()
    {
        return $this->_info;
    }
    public function setUrlCallback($func)
    {
        if ( is_callable($func))
        {
            $this->_urlCallback = $func;
        }
    }

    public function setTemplate($file)
    {
        $this->_file = $file;
    }
    public function render($file=null)
    {
        if($file !== null)
        {
            $this->_file = $file;
        }

        if( isset($this->_renderd[$this->_file]))
        {
            return $this->_renderd[$this->_file];
        }

        if (!$this->hasPages())
        {
            return 's';
        }
        ob_start();
        include '/home/jesper/dev.jesper.nu/site/tpl/_partial/'.$this->_file;

        return $this->_renderd[$this->_file] = ob_get_clean();
    }
    public function __toString()
    {
        return $this->render('paging.phtml');
    }

    public function isLastPage()
    {
        return $this->_info['current'] == $this->_info['pages'];
    }
    public function isFirstPage()
    {
        return $this->_info['current'] == 1;
    }
    public function isCurrentPage($page)
    {
        return $this->_info['current'] == $page->page;
    }

    private function _getStart($spred)
    {
        $half = ceil($spred/2);
        if (($this->_info['current'] - $half) < 2)
        {
        	$start = 2;
        }
        else
        {
            if( ($this->_info['current'] - $half) >= 1 &&
                ($this->_info['current'] + $half) < $this->_info['pages'] )
            {
                $start = ($this->_info['current'] - $half) + 1;
            }
            elseif ( ($this->_info['current'] + $half) >= $this->_info['pages'])
            {
                $start = ( $this->_info['pages'] - $spred ) + 1;
            }
        }
        return $start;
    }
    public function getRange($spred = 5)
    {
        $start = $this->_getStart($spred);
        $array = null;
        for ($i = 0; $i != $spred && $start != $this->_info['pages']; $i++ )
        {
            $data = array('page'=>$start,
                          'url'=>$this->_getUrl($start++));
            if ($i==0 && $start > 3) $data['isFirst'] = true;
            else $data['isFirst'] = false;

            if ($i==($spred-1)) $data['isLast'] = true;
            else $data['isLast'] = false;

            $array[] = (object)$data;
        }

        return $array;
    }

    public function getFirstPage()
    {
        return (object)array('page'=>1,'url'=>$this->_getUrl(1));
    }

    public function getLastPage()
    {
        return (object)array('page'=>$this->_info['pages'],
                             'url'=>$this->_getUrl($this->_info['pages']));
    }

    public function getNextPage()
    {
        if($this->isLastPage())
            return false;

        return (object)array('page'=>$this->_info['current']+1,
                             'url'=>$this->_getUrl($this->_info['current']+1));
    }
    public function getPreviusPage()
    {
        if($this->isFirstPage())
            return false;

        return (object)array('page'=>$this->_info['current']-1,
                             'url'=>$this->_getUrl($this->_info['current']-1));
    }

    public function getNrOfPages()
    {
        return $this->_info['pages'];
    }
    protected function _getUrl($page)
    {
        return call_user_func_array($this->_urlCallback,array($page));
    }
}