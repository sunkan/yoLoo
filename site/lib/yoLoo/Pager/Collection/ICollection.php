<?php
namespace yoLoo\Pager\Collection;

interface ICollection
{
    public function getPager();
    public function setPager(\yoLoo\Pager\IPager $pager);

    public function setPagerInfo(array $data);
    public function getPagerInfo();
}