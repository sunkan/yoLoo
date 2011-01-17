<?php
namespace J\Cvs;

class Handler
{
    protected $_db = null;
    protected $_user = null;

    public function getNewVersionNr($key)
    {
        $sql = 'SELECT MAX( `version` )  FROM jesper_cvs.`revision` WHERE doc = ?';

        $stmt = $this->_db->prepare($sql);
        $rs = $stmt->execute(array($key));
        $nr = (int)$stmt->fetchColumn(0);

        return ($nr+1);
    }

    public function commit($doc, $data)
    {
        $versionNr = $this->getNewVersionNr($doc);
        $sql = 'INSERT INTO jesper_cvs.revision (doc, version, data, user_id ) VALUES(:doc, :version, :data, :user_id)';
        $params[':doc'] = $key;
        $params[':version'] = $versionNr;
        $params[':data'] = $data;
        $params[':user_id'] = $this->_user->getId();
        
        $stmt = $this->_db->prepare($sql);
        $rs = $stmt->execute($params);
        if ($rs)
        {
            return $versionNr;
        }
        return false;
    }

    public function setDb(\yoLoo\Db\IConnection $conn)
    {
        $this->_db = $conn;
    }
    public function getDb()
    {
        return $this->_db;
    }

    public function addObsever(Observer\IObserver $observer)
    {
        $observer->setHandler($this);
        $this->_observer[] = $observer;
    }

    public function onPostExecute($event)
    {
        $parts = $this->_parseSql($event['sql'],$event['params']);
        if ($parts['type']=='insert')
        {
            $parts['id'] = $event['conn']->lastInsertId();
        }
        foreach ($this->_observers as $observer)
        {
            if ($observer->accepts($parts['db'],$parts['table'],$parts['type']))
            {
                $observer->update($parts);
            }
        }
    }

    public function revert(\yoLoo\Db\Mapper\Object $obj, $version)
    {
        $doc = $obj->getRevisionKey();
        $sql = 'SELECT * FROM jesper_cvs.`revision` WHERE doc = ? AND version = ? LIMIT 1';

        $stmt = $this->_db->prepare($sql);
        $rs = $stmt->execute(array($doc, $version));
        $row = $stmt->fetch();

        $obj->getMapper()->update(unserialize($row->data),$obj->getId());

        return ;
    }
    protected function _getId($sql,$params)
    {
        if (stripos($sql, 'update')===false)
        {
            return null;
        }
        $wPos = strripos($sql, ' where ');
        $ePos = (($ePos = strripos($sql, ' limit '))===false?strlen($sql):$ePos)-$wPos;
        $where = substr($sql, $wPos,$ePos);
        
        if (($pos=stripos($where, ' id'))===false)
        {
            return null;
        }
        $length = stripos($where, ' and ',$pos)-$pos;
        if ((!$length || $length < 0) || (stripos($where, ' or ',$pos) < stripos($where, ' and ',$pos)))
        {
            $length = stripos($where, ' or ',$pos)-$pos;
        }

        if (!$length || $length < 0)
        {
            $length = strlen($where)-$pos;
        }
        $str = substr($where, $pos, $length);
        $str = str_replace(' ', '', $str);
        if (stripos($str,'id=')===false)
        {
            return null;
        }
        $v = substr($str, 3);
        if ($v[0]==':' || $v[0]=='?')
        {
            if ($v[0]==':')
            {
                return $params[$v];
            }
            $startCount = substr_count($sql, $v[0],0,$wPos)-1;
            $beforCount = substr_count($where , '?', 0, $pos);
            $count = substr_count($where, '?',$pos,$length);
            return $params[$startCount+$count+$beforCount];
        }
        else
        {
            return $v;
        }
    }
    protected function _parseUpdateCols($sql)
    {

        $ignor = false;
        $s = stripos($sql, ' set ');
        $w = strripos($sql, ' where ');
        $cols = substr($sql, $s+5, $w-$s-5);
        $tok = strtok($cols, ",()");
        $data = array();
        while ($tok !== false) {
            if(!$ignor){
                list($k, $v) = explode('=',$tok);
                $data[trim($k)] = trim($v);
            }
            else
            {
                $data[$key] .= $tok;
            }
            if (substr($cols, strpos($cols, $tok)+strlen($tok),1)=='(')
            {
                $ignor=true;
                list($k, $v) = explode('=',$tok);
                $key = trim($k);
                $data[$key] = $v.'(';
                $tok = strtok(")");
            }
            else
            {
                if ($ignor)
                {
                    $data[$key] .= ')';
                    $key = null;
                }
                $ignor=false;
                $tok = strtok(",()");
            }
        }
        return $data;
    }
    protected function _parseSql($sql , $data)
    {
        $return = array();

        $sql = strtolower($sql);
        $type = \trim(\substr($sql, 0,\stripos($sql, ' ')));

        $return['type'] = $type;
        list($db, $tbl) = $this->_getDbAndTable($type, $sql);
        $return['db'] = $db;
        $return['table'] = $tbl;

        switch ($type) {
            case 'update':
                $cols = $this->_parseUpdateCols($sql);
                $return['id'] = $this->_getId($sql,$data);
                $i = 0;
                foreach ($cols as $col=>$val)
                {
                    if (trim($val)=='?')
                    {
                        $return['cols'][$col] = $data[$i++];
                    }
                    elseif(substr(trim($val),0,1)==':')
                    {
                        $return['cols'][$col] = $data[trim($val)];
                    }
                    else
                    {
                        $return['cols'][$col] = trim($val);
                    }
                }
                break;
            case 'insert':
                $sPos = strpos($sql,'(');
                $ePos = strpos($sql, ')');

                $keys  = array_map('trim',explode(',', substr($sql, $sPos+1,$ePos-$sPos-1)));
                $vsPos = strpos($sql, '(',$ePos);
                $vePos = strrpos($sql, ')');
                $vals  = array_map('trim',explode(',', substr($sql, $vsPos+1,$vePos-$vePos-1)));
                for ($i=0,$p=0,$c=count($keys);$i<$c;$i++)
                {
                    if ($vals[$i][0]=='?')
                    {
                        $return['cols'][$keys[$i]] = $data[$p];
                        $p++;
                    }
                    elseif($vals[$i][0]==':')
                    {
                        $return['cols'][$keys[$i]] = $data[$vals[$i]];
                    }
                    else
                    {
                        $return['cols'][$keys[$i]] = $vals[$i];
                    }
                }
                break;
            default:
                break;
        }
        return $return;
    }

    protected function _getDbAndTable($type, $sql)
    {

        switch ($type)
        {
            case 'select' :
            case 'delete' :
                $sqlTmp = \trim(\substr($sql,\stripos($sql, ' from ')+6));
                $e = \stripos($sqlTmp, ' ');
                if( \stripos($sqlTmp, ' ')===false)
                {
                    $e = \strlen($sqlTmp);
                }

                $dbandtbl = \substr($sqlTmp, 0, $e);
                break;
            case 'update' :
                list($dbandtbl,$r) = \array_map('trim', \explode(' ',\trim(\substr($sql, 7)),2));
                break;
            case 'insert' :
                list($dbandtbl,$r) = \array_map('trim', \explode(' ',\trim(\substr($sql, 12)),2));
                break;
            case 'use':
                $dbandtbl = strtolower(trim(substr($sql, 3)));

                break;
            default:
                $dbandtbl = null;
                break;
        }
        $dbandtbl = \explode('.', $dbandtbl);
        if(\count($dbandtbl) > 1)
        {
            list($db, $tbl) = $dbandtbl;
        }
        else
        {
            $tbl = $dbandtbl[0];
            $db = null;
        }

        return array($db, $table);
    }
}