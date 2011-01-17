<?php
namespace yoLoo\Sphinx;

class Result extends \yoLoo\Base implements \IteratorAggregate
{
    protected $_indexer = null;
    protected $_result = array();
    protected $_iterator = null;
    protected $_yoLoo_Sphinx_Result = array(
        'iterator'=>'\\yoLoo\Sphinx\\Result\\Iterator'
    );
    public function __construct(array $conf)
    {
        parent::__construct($conf);
    }
    public function setResult(array $data)
    {
        $this->_result = $data;
        return $this;
    }
    public function setIndex(\yoLoo\Sphinx\Index $indexer)
    {
        $this->_indexer = $indexer;
        return $this;
    }

    public function getRange()
    {
        $offset = $this->_indexer->getOffset();
        $limit = $this->_indexer->getLimit()+$offset;
        if($limit > $this->getNrOfTotaltResults())
            $limit = $this->getNrOfTotaltResults();

        return sprintf('%d - %d',($offset+1),($limit));
    }
    public function getExecuteTime()
    {
        return $this->_result['time'];
    }
    public function getNrOfResults()
    {
        return $this->_result['total'];
    }
    public function getNrOfTotaltResults()
    {
        return $this->_result['total_found'];
    }
    public function getSearchWords()
    {
        return array_keys($this->_result['words']);
    }
    public function getWordStats($word)
    {
        return $this->_result['words'][$word];
    }
    public function getTotalNrOfPages()
    {
        return ceil($this->getNrOfTotaltResults()/$this->_indexer->getLimit());
    }

    public function getResult()
    {
        return $this->_result['matches'];
    }

    public function getIterator()
    {
        if (\is_string($this->_config['iterator']))
        {
            $class = $this->_config['iterator'];
            $this->_iterator = new $class();
            
        }
    
        if ($this->_iterator instanceof \Iterator)
        {
            $this->_iterator->addData($this->getResult());
        }
        else
        {
            throw new \yoLoo\Sphinx\Exception('Iterator must implement: Iterator interface');
        }

        return $this->_iterator;
    }
}