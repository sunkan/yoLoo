<?php
namespace yoLoo\Memcache\Key;

class SortedSet extends Base implements \Countable, \ArrayAccess, \IteratorAggregate
{
    protected $_data = null;
    protected $_autoSave = true;
    protected function _load()
    {
        if ($this->_data === null)
        {
            $this->_data = \unserialize(parent::_load());
            if (!is_array($this->_data)) $this->_data = array();
            ksort($this->_data);
        }
        return $this->_data;
    }
    protected function _save()
    {
        \ksort($this->_data);
        return parent::_save(\serialize($this->_data));
    }

    public function add($value, $score)
    {
        $this->_autoSave = false;
        $this->remove($value);
        $this->_autoSave = true;
        $this->_data[$score] = $value;
        if ($this->_autoSave)
        {
            $this->_save();
        }

        return $this;
    }
    public function remove($value)
    {
        foreach($this->_load() as $key=>$v)
        {
            if ($v == $value)
            {
                unset($this->_data[$key]);
            }
        }
        if ($this->_autoSave)
        {
            $this->_save();
        }

        return $this;
    }

    public function count()
    {
        return count($this->_load());
    }

    public function getByScore($min, $max, $withScores = false, $limit = null, $offset = null)
    {
        $result = array();
        $rank = 0;
        foreach ($this->_load() as $score=>$value)
        {
            if ($score >= $min && $score <= $max)
            {
                if ($withScores)
                {
                    $result[$score] = $value;
                }
                else
                {
                    $result[$rank] = $value;
                }
            }
            $rank++;
        }
        return $result;
    }
    
    public function getIterator()
    {
        return new \ArrayObject($this->toArray());
    }


   public function removeByScore($min, $max)
    {
        foreach ($this->_load() as $score=>$value)
        {
            if ($score >= $min && $score <= $max)
            {
                unset($this->_data[$score]);
            }
        }
        return $this->_save();
    }
    public function getScore($value)
    {
        foreach($this->_data as $score=>$v)
        {
            if ($v == $value)
            {
                return $score;
            }
        }
    }
    public function incrementScore($value, $score)
    {

    }
    public function removeByRank($start, $end)
    {
        $keys = array_keys($this->_load());
        for (;$start<=$end;$start++)
        {
            unset($this->_data[$keys[$start-1]]);
        }
        return $this->_save();
    }
    public function getRank($value)
    {
        $data = $this->_load();
        $rank = 0;
        foreach($data as $score=>$v)
        {
            if ($v == $value)
            {
                return $rank;
            }
            $rank++;
        }
    }

    public function toArray($withScores = false, $offset = 0, $limit = null, $revert = false)
    {
        $data = $this->_load();
        if ($revert)
        {
            $data = \array_reverse($data,true);
        }
        return \array_slice($data, $offset, $limit, true);
    }
    public function fromArray(array $data)
    {
        $this->_data = \array_merge($this->_load(),$data);
        $this->_save();
    }

    public function offsetSet($score, $value)
    {
        if (is_null($score))
        {
            throw new \Exception('Score must be present');
        }

        $this->add($value, $score);

        return $value;
    }

    public function offsetExists($score)
    {
        return (boolean)$this->offsetGet($score);
    }

    public function offsetUnset($score)
    {
        $value = $this->offsetGet($score);
        if (!is_null($value))
        {
            $this->remove($value);

            return true;
        }
        else
        {
            return false;
        }
    }

    public function offsetGet($score)
    {
        $values = $this->getByScore($score, $score);

        if (!empty($values))
        {
            return $values[0];
        }
    }
}