<?php

namespace Control\User;

use Core\core;

class login{
    public function register(){
        $email = _getParameter('email', '');
        $password = _getParameter('password', '');
        $result = core::_loadClass('Business/User/Login')->doRegister($email, $password);
        var_dump($result);
        exit;
    }
}
