<?php
namespace yoLoo\I18n;

class ATranslation
{
    protected function _array_search($needle, array $haystack)
    {
        foreach($haystack as $key => $val)
        {
            if(mb_stripos($val,$needle) === 0)
            {
                return $key;
            }
        }
        return false;
    }
}