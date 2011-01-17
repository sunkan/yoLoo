<?php
namespace yoLoo\Auth;

interface IUser
{
    public function getUsername();
    public function getPassword();

    public function checkAuth($pass);
}