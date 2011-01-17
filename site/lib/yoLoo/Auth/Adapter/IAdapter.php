<?php
namespace yoLoo\Auth\Adapter;

interface IAdapter
{
    public function setUsername($user);
    public function setPassword($pass);

    public function authenticat();
    public function destroy();

    public function getUser();
}