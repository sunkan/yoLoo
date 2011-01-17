<?php
namespace J\Cvs\Observers;

class Video implements AObserver
{
    public function accepts($db, $table, $type)
    {
        return (in_array($db,    array('jesper_videos')) &&
                in_array($table, array('videos')) &&
                in_array($type,  array('insert','update','delete')));
    }
    public function update($data)
    {
        if ($data['table'] != 'videos')
        {
            return false;
        }
        $type = $data['type'];
        if (in_array($type, array('insert','update')))
        {
            $sql = strtolower($data['sql']);
            if ($type == 'update' && (isset($data['cols']['version']) || $data['id']===null))
            {
                return false;
            }
            $key = 'video:'.$data['id'];
            $versionNr = $this->_handler->commit($key, serialize($data['cols']));

            if ($versionNr!==false)
            {
                $sql = 'UPDATE jesper_videos.videos SET version = ? WHERE id = ? LIMIT 1';
                $stmt = $this->_db->prepare($sql);

                $stmt->execute(array($versionNr, $data['id']));
            }
        }
    }
}
