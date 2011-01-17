<?php
namespace yoLoo\Cache\Adapter;

class None extends Base
{
    /**
     *
     * Sets cache entry data.
     *
     * @param string $key The entry ID.
     *
     * @param mixed $data The data to write into the entry.
     *
     * @return true Always reports a successsful save.
     *
     */
    public function save($key, $data)
    {
        if(!$this->_active)
            return ;

        return true;
    }

    /**
     *
     * Inserts cache entry data, but only if the entry does not already exist.
     *
     * @param string $key The entry ID.
     *
     * @param mixed $data The data to write into the entry.
     *
     * @return true Always reports a successsful add.
     *
     */
    public function add($key, $data)
    {
        if(!$this->_active)
            return;

        return true;
    }

    /**
     *
     * Gets cache entry data.
     *
     * @param string $key The entry ID.
     *
     * @return true Always reports a failed fetch.
     *
     */
    public function fetch($key)
    {
        if(!$this->_active)
            return;

        return false;
    }
    
    public function increment($key, $amt = 1){}

    public function delete($key){}

    public function deleteAll(){}

    public function entry($key){}

}