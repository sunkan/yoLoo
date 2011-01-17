<?php
namespace yoLoo\Acl\Permission;

interface IPermission
{
    const ALLOW = 1;
    const DENY  = 2;
    public function is($section, $action);
    public function isAllowed();
    public function isOwner();
}