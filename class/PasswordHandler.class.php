<?php

class PasswordHandler{

    public function __construct(UserSQL $userSQL)
    {
        $this->userSQL = $userSQL;
    }

    public function passwordMatchesHash($password, $hash, $id){
        if($this->passwordIsMd5Encoded($hash)){
            $passwordMatchesHash = $this->passwordMatchesMd5Hash($password, $hash);
            if($passwordMatchesHash) {
                $this->updatePasswordHash($id, $password);
            }
            return $passwordMatchesHash;
        }
        return password_verify($password,$hash);
    }

    /**
     * @param $hash
     * @return bool
     */
    private function passwordIsMd5Encoded($hash): bool
    {
        return strlen($hash) == 32;
    }

    /**
     * @param $id
     * @param $password
     */
    private function updatePasswordHash($id, $password): void
    {
        $this->userSQL->setPassword(
            $id,
            password_hash($password, PASSWORD_DEFAULT)
        );
    }

    /**
     * @param $password
     * @param $hash
     * @return bool
     */
    private function passwordMatchesMd5Hash($password, $hash): bool
    {
        return md5($password) == $hash;
    }
}