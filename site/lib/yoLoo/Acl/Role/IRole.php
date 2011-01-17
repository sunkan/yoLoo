<?php
namespace yoLoo\Acl\Role;

interface IRole
{
    public function is($role);
    public function getRoleName();
    public function hasRole($role);
    public function addPremission(\yoLoo\Acl\Permission\IPermission $prem);
    public function addParents(array $parents);
    public function getParentRoles();

    public function hasPremission($section, $action);
    public function getPremission($section, $action);

    public function isAllowed($section, $action);
}