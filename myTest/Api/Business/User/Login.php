<?php

namespace Business\User;

use Core\core;

class Login{
    public function doRegister($email, $password){
        return core::_loadClass('Model/User/LOGIN')->doRegister($email, $password);
    }
}