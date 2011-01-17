<?php
namespace yoLoo\SessionHandler;

class Memcache extends Base
{
    protected $_yoLoo_SessionHandler_Memcache = array(
        'memcache' => array(
            'servers' => array(array(
                'host'=>'localhost',
                'port'=>11211,
                'weight'=>null
                ))
        ),
        'track'=>true
    );

    /**
     * Horde_Memcache object.
     *
     * @var Horde_Memcache
     */
    protected $_memcache;
    /**
     * Current session ID.
     *
     * @var string
     */
    protected $_id;
    /**
     * Do read-only get?
     *
     * @var boolean
     */
    protected $_readonly = false;
    /**
     * The ID used for session tracking.
     *
     * @var string
     */
    protected $_trackID = 'yoloo_memcache_sessions_track';

    public function __construct($conf) {
        $conf['memcache']['prefix'] = 'sess_';
        parent::__construct($conf);

    }
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     * @throws Horde_Exception
     **/
    protected function _postConstruct()
    {
         if (empty($this->_config['track_lifetime']))
             $this->_config['track_lifetime'] = ini_get('session.gc_maxlifetime');

         if (!empty($this->_config['track']) && (rand(0, 999) == 0))
             register_shutdown_function(array($this, 'trackGC'));
     }
     /**
      * Open the backend.
      *
      * @param string $save_path     The path to the session object.
      * @param string $session_name  The name of the session.
      * @throws Horde_Exception
      */
     protected function _open($save_path = null, $session_name = null)
     {
         if ($this->_memcache === null)
             $this->_memcache = new \yoLoo\Memcache($this->_config['memcache']);

         if (!$this->_memcache)
             throw new yoLoo\Exception('Could not open persistent backend.');
     }
     /**
      * Close the backend.
      *
      * @throws Horde_Exception
      */
     protected function _close()
     {
         if (isset($this->_id))
             $this->_memcache->unlock($this->_id);
     }
     /**
      * Read the data for a particular session identifier.
      *
      * @param string $id  The session identifier.
      * @return string  The session data.
      */
     protected function _read($id)
     {
         if (!$this->_readonly) 
             $this->_memcache->lock($id);
         
         $result = $this->_memcache->get($id);
         if ($result === false) {
             if (!$this->_readonly) 
                 $this->_memcache->unlock($id);
             
             if ($result === false) {
                 $this->log('Error retrieving session data (id = ' . $id . ')', __FILE__, __LINE__, \yoLoo\Interfaces\Log\DEBUG);
                 return false;
             }
         }
         if (!$this->_readonly) {
             $this->_id = $id;
         }
         $this->log('Read session data (id = ' . $id . ')', __FILE__, __LINE__, \yoLoo\Interfaces\Log\DEBUG);
         return $result;
     }
     /**
      * Write session data to the backend.
      *
      * @param string $id            The session identifier.
      * @param string $session_data  The session data.
      *
      * @return boolean  True on success, false otherwise.
      */
     protected function _write($id, $session_data)
     {
         if (!empty($this->_config['track'])) {
             // Do a replace - the only time it should fail is if we are
             // writing a session for the first time.  If that is the case,
             // update the session tracker.
             $res = $this->_memcache->replace($id, $session_data);
             $track = !$res;
         } else {
             $res = $track = false;
         }
         if (!$res && !$this->_memcache->set($id, $session_data)) {
             $this->log('Error writing session data (id = ' . $id . ')', __FILE__, __LINE__, \yoLoo\Interfaces\Log\ERROR);
             return false;
         }
         if ($track) {
             $this->_memcache->lock($this->_trackID);
             $ids = $this->_memcache->get($this->_trackID);
             if ($ids === false) {
                 $ids = array();
             }
             $ids[$id] = time();
             $this->_memcache->set($this->_trackID, $ids);
             $this->_memcache->unlock($this->_trackID);
         }
         $this->log('Wrote session data (id = ' . $id . ')', __FILE__, __LINE__, \yoLoo\Interfaces\Log\DEBUG);
         return true;
     }
     /**
      * Destroy the data for a particular session identifier.
      *
      * @param string $id  The session identifier.
      *
      * @return boolean  True on success, false otherwise.
      */
     public function destroy($id)
     {
         $result = $this->_memcache->delete($id);
         $this->_memcache->unlock($id);
         if ($result === false) {
             $this->log('Failed to delete session (id = ' . $id . ')', __FILE__, __LINE__, \yoLoo\Interfaces\Log\DEBUG);
             return false;
         }
         if (!empty($this->_params['track'])) {
             $this->_memcache->lock($this->_trackID);
             $ids = $this->_memcache->get($this->_trackID);
             if ($ids !== false) {
                 unset($ids[$id]);
                 $this->_memcache->set($this->_trackID, $ids);
             }
             $this->_memcache->unlock($this->_trackID);
         }
         $this->log('Deleted session data (id = ' . $id . ')', __FILE__, __LINE__, \yoLoo\Interfaces\Log\DEBUG);
         return true;
     }
     /**
      * Garbage collect stale sessions from the backend.
      *
      * @param integer $maxlifetime  The maximum age of a session.
      *
      * @return boolean  True on success, false otherwise.
      */
     public function gc($maxlifetime = 300)
     {
         // Memcache does its own garbage collection.
         return true;
     }
     /**
      * Get a list of (possibly) valid session identifiers.
      *
      * @return array  A list of session identifiers.
      * @throws Horde_Exception
      */
     public function getSessionIDs()
     {
         try {
             $this->_open();
             if (empty($this->_config['track'])) {
                 throw new yoLoo\Exception("Memcache session tracking not enabled.");
             }
         } catch (yoLoo\Exception $e) {
             throw $e;
         }
         $this->trackGC();
         $ids = $this->_memcache->get($this->_trackID);

         return ($ids === false) ? array() : array_keys($ids);
     }
     /**
      * Get session data read-only.
      *
      * @param string $id  The session identifier.
      *
      * @return string  The session data.
      */
     protected function _readOnly($id)
     {
         $this->_readonly = true;
         $result = $this->_memcache->get($id);
         $this->_readonly = false;
         return $result;
     }
     /**
      * Do garbage collection for session tracking information.
      */
     public function trackGC()
     {
         $this->_memcache->lock($this->_trackID);
         $ids = $this->_memcache->get($this->_trackID);
         if (empty($ids)) {
             $this->_memcache->unlock($this->_trackID);
             return;
         }
         $tstamp = time() - $this->_config['track_lifetime'];
         $alter = false;
         foreach ($ids as $key => $val) {
             if ($tstamp > $val) {
                 unset($ids[$key]);
                 $alter = true;
             }
         }
         if ($alter) {
             $this->_memcache->set($this->_trackID, $ids);
         }
         $this->_memcache->unlock($this->_trackID);
     }
}
