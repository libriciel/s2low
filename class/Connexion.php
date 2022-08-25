<?php

namespace S2lowLegacy\Class;

class Connexion
{
    public function isConnected()
    {
        return ! empty($_SESSION['id_login']);
    }

    public function getId()
    {
        if (! $this->isConnected()) {
            return false;
        }
        return $_SESSION['id_login'];
    }

    public function connect($id_u)
    {
        $_SESSION['id_login'] = $id_u;
    }
}
