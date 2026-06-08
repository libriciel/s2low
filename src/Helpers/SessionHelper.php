<?php

namespace S2low\Helpers;

class SessionHelper
{
    /**
     * @param string $name
     * @param bool $delete
     * @return mixed
     */
    public function getFromSession(string $name, bool $delete = true): mixed
    {
        $ret = null;
        if (isset($_SESSION["temp"][$name])) {
            $ret = $_SESSION["temp"][$name];
        }

        if ($delete) {
            unset($_SESSION["temp"][$name]);
        }

        return $ret;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function putInSession(string $name, mixed $value): void
    {
        $_SESSION["temp"][$name] = $value;
    }

    /**
     * @return void
     */
    public function purgeTempSession(): void
    {
        unset($_SESSION["temp"]);
    }
}
