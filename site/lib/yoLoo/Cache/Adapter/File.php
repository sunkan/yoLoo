<?php
namespace yoLoo\Cache\Adapter;


class File extends Base
{
    protected $_yoLoo_Cache_Adapter_File = array(
        'path'    => null, // default set in constructor
        'mode'    => 0740,
        'context' => null,
        'hash'    => true,
    );

    protected $path = null;

    protected $hash = null;

    protected $context = null;


    protected function _postConstruct()
    {
        // path to storage; include the prefix as part of the path
        $this->_path = $this->_config['path'] . '/' . $this->_prefix;

        // whether or not to hash
        $this->_hash = (bool)$this->_config['hash'];

        // build the context property
        if (is_resource($this->_config['context'])) {
            $this->_context = $this->_config['context'];
        } elseif (is_array($this->_config['context'])) {
            // create from scratch
            $this->_context = stream_context_create($this->_config['context']);
        } else {
            // not a resource, not an array, so ignore.
            // have to use a resource of some sort, so create
            // a blank context resource.
            $this->_context = stream_context_create(array());
        }

        // make sure the cache directory is there; create it if
        // necessary.
        if (! is_dir($this->_path)) {
            mkdir($this->_path, $this->_config['mode'], true, $this->_context);
        }
    }

    public function save($key, $data)
    {
        if(!$this->_active)
            return;


        // should the data be serialized?
        // serialize all non-scalar data.
        if (! is_scalar($data)) {
            $data = serialize($data);
            $serial = true;
        } else {
            $serial = false;
        }

        // what file should we write to?
        $file = $this->entry($key);

        // does the directory exist?
        $dir = dirname($file);
        if (! is_dir($dir)) {
            mkdir($dir, $this->_config['mode'], true, $this->_context);
        }

        // open the file for over-writing. not using file_put_contents
        // becuase we may need to write a serial file too (and avoid race
        // conditions while doing so). don't use include path. using ab+ is
        // much, much faster than wb.
        $fp = fopen($file, 'ab+', false, $this->_context);

        // was it opened?
        if (! $fp) {
            // could not open the file for writing.
            return false;
        }

        // set exclusive lock for writing.
        flock($fp, LOCK_EX);

        // empty whatever might be there and the write
        fseek($fp, 0);
        ftruncate($fp, 0);
        fwrite($fp, $data);

        // add a .serial file? (do this while the file is locked to avoid
        // race conditions)
        if ($serial) {
            // use this instead of touch() because it supports stream
            // contexts.
            file_put_contents($file . '.serial', null, LOCK_EX, $this->_context);
        } else {
            // make sure no serial file is there from any previous entries
            // with the same name
            @unlink($file . '.serial', $this->_context);
        }

        fclose($fp);  // releases the lock
        return true;
    }
    public function add($key, $data)
    {
        if(!$this->_active)
            return;

        // what file should we look for?
        $file = $this->entry($key);

        // is the key available for adding?
        $available = ! file_exists($file) ||
                     ! is_readable($file) ||
                     $this->_isExpired($file);

        if ($available)
            return $this->save($key, $data);

        // key already exists
        return false;
    }
    public function fetch($key)
    {
        if(!$this->_active)
            return;

        // get the entry filename *before* validating;
        // this avoids race conditions.
        $file = $this->entry($key);

        // make sure the file exists and is readable
        if (! file_exists($file) || ! is_readable($file)) {
            return false;
        }

        // make sure file is still within its lifetime
        if ($this->_isExpired($file)) {
            // expired, remove it
            $this->delete($key);
            return false;
        }

        // the file data, if we can open the file.
        $data = false;

        // file exists and is not expired; open it for reading
        $fp = @fopen($file, 'rb', false, $this->_context);

        // could it be opened?
        if ($fp) {

            // lock the file right away
            flock($fp, LOCK_SH);

            // get the cache entry data.
            $data = stream_get_contents($fp);

            // check for serializing while file is locked
            // to avoid race conditions
            if (file_exists($file . '.serial')) {
                $data = unserialize($data);
            }

            fclose($fp); // releases lock
        }

        // will be false if fopen() failed, otherwise is the file contents.
        return $data;
    }
    protected function _isExpired($file)
    {
        if ($this->_life) {
            if (time() > filemtime($file) + $this->_config['life'])
                return true;
        }
        // lifetime is forever, or not past expiration yet.
        return false;
    }
    public function increment($key, $amt = 1)
    {
        if(!$this->_active)
            return;

        // make sure we have a key to increment
        $this->add($key, '0', null, $this->_life);

        // what file should we write to?
        $file = $this->entry($key);

        // open the file for read/write.
        $fp = fopen($file, 'r+b', false, $this->_context);

        // was it opened?
        if (! $fp) {
            return false;
        }

        // set exclusive lock for read/write.
        flock($fp, LOCK_EX);

        // PHP caches file lengths; clear that out so we get
        // an accurate file length.
        clearstatcache();
        $len = filesize($file);

        // get the current value and increment it
        $val = fread($fp, $len);
        $val += $amt;

        // clear the file, rewind the pointer, and write the new value
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $val);

        // unlock and close, then done.
        flock($fp, LOCK_UN);
        fclose($fp);
        return $val;
    }
    public function delete($key)
    {
        if (! $this->_active) {
            return;
        }

        $file = $this->entry($key);
        @unlink($file, $this->_context);
        @unlink($file . '.serial', $this->_context);
    }
    public function deleteAll()
    {
        if (! $this->_active) {
            return;
        }

        // get the list of files in the directory, suppress warnings.
        $list = (array) @scandir($this->_path, null, $this->_context);
        
        // delete each file
        foreach ($list as $file) {
            @unlink($this->_path . $file, $this->_context);
        }
    }
    public function entry($key)
    {
        if ($this->_config['hash']) {
            return $this->_path . hash('md5', $key);
        } else {
            // try to avoid file traversal exploits
            $key = str_replace('..', '_', $key);
            // colons mess up Mac OS X
            $key = str_replace(':', '_', $key);
            // done
            return $this->_path . $key;
        }
    }
}