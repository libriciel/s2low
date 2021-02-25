<?php

class PasswordHandler{

    public function __construct(UserSQL $userSQL)
    {
        $this->userSQL = $userSQL;
    }

    public function passwordMatchesHash($password, $hash, $id){
        if(strlen($hash) == 32){
            if(md5($password) == $hash){
                $this->userSQL->setPassword(
                    $id,
                    password_hash($password,PASSWORD_DEFAULT)
                );
                return true;
            }
            return false;
        }
        if(password_verify($password,$hash)){
            return true;
        }
        return false;
    }
}