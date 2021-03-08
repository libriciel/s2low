<?php

class PasswordHandler{

    /** @var UserSQL  */
    private $userSQL;

    public function __construct(UserSQL $userSQL)
    {
        $this->userSQL = $userSQL;
    }

    public function passwordMatchesHash(string $password,string $hash,int $id) : bool
    {
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
    private function passwordIsMd5Encoded(string $hash): bool
    {
        return strlen($hash) === 32;
    }

    /**
     * @param $id
     * @param $password
     */
    private function updatePasswordHash(int $id, string $password): void
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
    private function passwordMatchesMd5Hash(string $password, string $hash): bool
    {
        return md5($password) === $hash;
    }
}