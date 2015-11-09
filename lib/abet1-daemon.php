<?php

/* abet-daemon.php - ABET1 control daemon

    This program operates as a system daemon. It handles tasks that the Web
    server cannot for the ABET1 application. These tasks include:
        - user profile notifications (i.e. chronology/sending/management)
        - accredited program status report notifications (digest)
*/

// globals
$STDIN = STDIN;
$STDOUT = STDOUT;
$STDERR = STDERR;
$PIDFILE = null;

// constants
define('APP_PREFIX',"abet1");
define('LOG_FILE',"/var/log/abet1.log");
define('ERROR_FILE',"/var/log/abet1.errors.log");

// log_message() - log message to standard output; this should go to a log
// file that the daemon creates
function log_message($what) {
    global $STDOUT;

    $tstamp = date("D M j Y H:i:s e");
    $pid = posix_getpid();
    fwrite($STDOUT,"[pid=$pid @$tstamp] $what\n");
}

// fatal_error() - display error message and die
function fatal_error($why) {
    global $STDERR;

    $tstamp = date("D M j Y H:i:s e");
    $pid = posix_getpid();
    fwrite($STDERR,"[pid=$pid @$tstamp] $why\n");
    exit(1);
}

// daemonize() - become a system daemon
function daemonize() {
    global $STDIN;
    global $STDOUT;
    global $STDERR;
    global $PIDFILE;
    global $argv;

    // become a daemon
    $pid = pcntl_fork();
    if ($pid == -1)
        fatal_error("pcntl_fork() failed");
    if ($pid == 0) { // child process that will become the daemon
        // become the leader of a new session
        if (posix_setsid() == -1)
            fatal_error("posix_setsid() failed");

        // reset umask
        umask(0);

        // set working directory to root
        if (!chdir('/'))
            fatal_error("chdir() failed");

        // redirect STDOUT, STDIN and STDERR; we'll use PHP functionality
        // for this; handle each one separately so the system assigns the
        // correct descriptor; this will not work on Windows or with mod_php; on
        // Linux the next available file descriptor number is used so we open
        // them in order from STDIN to STDERR
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $STDIN = @fopen('/dev/null','r');
        if (!$STDIN) {
            fatal_error("fopen() failed (stdin)");
        }
        $STDOUT = @fopen(LOG_FILE,'a');
        if (!$STDOUT) {
            $STDOUT = @fopen('/tmp/abet1.log','a');
            if (!$STDOUT)
                fatal_error("fopen() failed (stdout)");
        }
        $STDERR = @fopen(ERROR_FILE,'a');
        if (!$STDERR) {
            $STDERR = @fopen('/tmp/abet1.errors.log','a');
            if (!$STDERR)
                fatal_error("fopen() failed (stderr)");
        }

        log_message("process started");

        // process optional arguments: the first argument is a pidfile
        if (array_key_exists(1,$argv)) {
            $PIDFILE = $argv[1];
            if (file_exists($PIDFILE)) {
                fatal_error("the PID file already exists; this means another "
                    . "instance may be running; if not, then delete the file $PIDFILE");
            }
            $pf = @fopen($PIDFILE,'w');
            if (!$pf)
                fatal_error("couldn't create the PID file; process will now terminate");
            fwrite($pf,posix_getpid());
            fclose($pf);
            log_message("created PID file '$PIDFILE'");
        }
    }
    else {
        fwrite($STDOUT,"abet-daemon: process started\n");
        exit(0);
    }
}

function check_pidfile() {
    global $PIDFILE;

    // make sure the pidfile still holds our pid; if not, then terminate; only
    // worry about this if the file exists; only someone with the correct
    // permissions could have modified the file
    if (!is_null($PIDFILE)) {
        $pf = @fopen($PIDFILE,'r');
        if (is_resource($pf)) {
            $pid = intval(fgets($pf));
            fclose($pf);
            if ($pid != posix_getpid()) {
                log_message("noticied change in PID file; will now terminate");
                $PIDFILE = null; // prevent the file from being unlinked
                terminate();
            }
        }
    }
}

function terminate($signo = -1) {
    global $PIDFILE;
    log_message("process terminated");

    if (!is_null($PIDFILE)) {
        // delete pidfile
        unlink($PIDFILE);
    }

    exit(0);
}

////////////////////////////////////////////////////////////////////////////////
// main program operation //////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
daemonize(); // I normally don't approve of demonizing anyone

// setup signal handlers
declare(ticks = 1);
pcntl_signal(SIGTERM,'terminate');
pcntl_signal(SIGINT,'terminate');
pcntl_signal(SIGQUIT,'terminate');
pcntl_signal(SIGPIPE,SIG_IGN);

// enter into wait loop
while (true) {
    check_pidfile();

    // timeout for a minute
    sleep(60);
}

terminate();
