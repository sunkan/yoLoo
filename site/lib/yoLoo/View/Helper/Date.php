<?php
namespace yoLoo\View\Helper;

class Date extends \yoLoo\View\Helper\Base
{
    public function date($date=null, $format=null,$show_time = true)
    {
        if ($date === null)
        {
            return $this;
        }
        if ($date instanceof \yoLoo\Db\Mapper\Object)
        {
            $date = $date->getDate($format);
            $format = null;
        }
        if ($date=='0000-00-00 00:00:00')
        {
            return 'Not a valid date';
        }
        $timestamp = strtotime($date);

        if ($format!=null && $format!=false)
        {
            return date($format,$timestamp);
        }
        $date = date('Y-m-d',$timestamp);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d',strtotime('-1 day'));
        $week = strtotime('-5 day');

        $str = '';

        if ($date==$today)
            $str .= 'idag ';
        elseif ($date==$yesterday)
            $str .= 'ig&aring;r ';
        elseif ($week<$timestamp)
            $str .= strftime('i %As ',$timestamp);
        else{
             $str .= strftime('%e %B %Y',$timestamp);
            if ($show_time)
            {
                $str .= ', ';
            }
        }

        if ($show_time)
            $str .= date(' H:i',$timestamp);

        return utf8_encode($str);
    }
}
