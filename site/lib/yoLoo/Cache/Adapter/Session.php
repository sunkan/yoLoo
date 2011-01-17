<?php
namespace yoLoo\Cache\Adapter;


class Session extends Base
{
    protected $_entries = null;
    protected $_expores = array();

    protected function _postConstruct()
    {
        parent::_postConstruct();

        // prefix the session class store, not the individual keys.
        $prefix = $this->_prefix;
        $this->_prefix = null;
        if (! $prefix) {
            $prefix = 'yoLoo_Cache_Adapter_Session';
        }

        // a session store for entries
        $this->_entries = $_SESSION[$prefix.'__entries'];

        // a session store for expires
        $this->_expires = $_SESSION[$prefix . '__expires'];
    }
    public function save($key, $data)
    {
        if(!$this->_active)
            return;

        // modify the key to add the prefix
        $key = $this->entry($key);

        // save entry and expiry in session
        $this->_entries[$key] = $data;
        $this->_expires[$key] = time() + $this->_life;
        return true;
    }

    public function add($key, $data)
    {
        if(!$this->_active)
            return;

        // modify the key to add the prefix
        $key = $this->entry($key);

        // add entry to session if not already there
        if (!isset($this->_entries[$key])) {
            return $this->save($key, $data);
        } else {
            return false;
        }
    }

    public function fetch($key)
    {
        if(!$this->_active)
            return;

        // modify the key to add the prefix
        $key = $this->entry($key);

        // does it exist?
        if (!isset($this->_entries[$key]))
            return false;

        // has it expired?
        if ($this->_isExpired($key)) {
            // clear the entry
            unset($this->_entries[$key]);
            unset($this->_expires[$key]);
            return false;
        }

        // return the value
        return $this->_entries[$key];
    }
    public function increment($key, $amt = 1)
    {
        if(!$this->_active)
            return;

        // modify the key to add the prefix
        $key = $this->entry($key);

        // make sure we have a key to increment
        $this->add($key, 0);

        // increment it
        $val = $this->_entries[$key];
        $this->_entries[$key] = $val + $amt;

        // done!
        return $this->_entries[$key];
    }
    public function delete($key)
    {
        if(!$this->_active)
            return;

        // modify the key to add the prefix
        $key = $this->entry($key);

        // delete entry and expiry
        unset($this->_entries[$key]);
        unset($this->_expires[$key]);
    }
    public function deleteAll()
    {
        if(!$this->_active)
            return;

        unset($this->_entries);
        unset($this->_expires);
    }
    protected function _isExpired($key)
    {
        // is life set as "forever?"
        if (! $this->_life)
            return false;

        // is it past its expiration date?
        if (time() >= $this->_expires[$key])
            return true;

        // not expired yet
        return false;
    }
}