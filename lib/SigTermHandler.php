<?php

namespace S2lowLegacy\Lib;

class SigTermHandler
{
    private const SIGNO_TO_HANDLE = [
        15,//SIGTERM,   TODO : utiliser les constantes
        2, //SIGINT
    ];

    private static $instance;

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new SigTermHandler();
        }
        return self::$instance;
    }

    private $sig_term_called = false;
    private $last_signo;
    private $exit_on_signal = false;

    private $is_activated = false;

    public function __construct()
    {
        /* Nothing to do */
    }

    public function activate()
    {

        if ($this->is_activated) {
            return ;
        }

        pcntl_async_signals(true);

        $f = function ($signo) {
            $this->sig_term_called = true;
            $this->last_signo = $signo;
            if ($this->exit_on_signal) {
                exit(0);
            }
        };
        foreach (self::SIGNO_TO_HANDLE as $signo) {
            pcntl_signal($signo, $f);
        }
        $this->is_activated = true;
    }


    public function isSigtermCalled()
    {
        $this->activate();
        return $this->sig_term_called;
    }

    public function getLastSigNo()
    {
        return $this->last_signo;
    }

    public function setExitOnSignal(bool $exit_on_signal)
    {
        $this->activate();
        $this->exit_on_signal = $exit_on_signal;
    }
}
