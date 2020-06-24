
<?php
// tick use required
declare(ticks = 1);

// signal handler function
function sig_handler($signo)
{

    switch ($signo) {
        case SIGTERM:
            echo 'SIGTERM\n';
            // handle shutdown tasks
            exit;
            break;
        case SIGHUP:
            echo 'SIGHUP\n';
            // handle restart tasks
            break;
        case SIGUSR1:
            echo "Caught SIGUSR1...\n";
            break;
        default:
            echo "autre\n";
            // handle all other signals
    }

}

echo "Installing signal handler...\n";

// setup signal handlers
pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");
pcntl_signal(SIGINT, "sig_handler");

// or use an object
// pcntl_signal(SIGUSR1, array($obj, "do_something"));

sleep(100000000000);
echo"Generating signal SIGUSR1 to self...\n";

// send SIGUSR1 to current process id
// posix_* functions require the posix extension
//posix_kill(posix_getpid(), SIGUSR1);

echo "Done\n";

?>
