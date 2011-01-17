<?php
namespace yoLoo\Auth;

interface User
{
    public function getUsername();
    public function getPassword();

    public function update();
}