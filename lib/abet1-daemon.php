<?php

/* abet-daemon.php - ABET1 control daemon

    This program operates as a system daemon. It handles tasks that the Web
    server cannot for the ABET1 application. These tasks include:
        - user profile notifications (i.e. chronology/sending/management)
        - accredited program status report notifications (digest)
*/

////////////////////////////////////////////////////////////////////////////////
// globals
////////////////////////////////////////////////////////////////////////////////

$STDIN = STDIN;
$STDOUT = STDOUT;
$STDERR = STDERR;
$PIDFILE = null;
$RESTART = false;
$INCLUDES = array(
    'abet1-query.php'
);

// constants
define('APP_PREFIX',"abet1");
define('LOG_FILE',"/var/log/abet1.log");
define('ERROR_FILE',"/var/log/abet1.errors.log");

////////////////////////////////////////////////////////////////////////////////
// includes
////////////////////////////////////////////////////////////////////////////////

// include all required php sources; then grab file modification values
set_include_path('/usr/lib/abet1:/usr/local/lib/abet1');
$INCLUDE_MTIMES = array();
foreach ($INCLUDES as $file) {
    if ((include $file) === false)
        fatal_error("could not include library '$file'");

    $qfile = stream_resolve_include_path($file);
    $INCLUDE_MTIMES[$qfile] = filemtime($qfile);
}

////////////////////////////////////////////////////////////////////////////////
// functions
////////////////////////////////////////////////////////////////////////////////

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

    // become a daemon: keep the current directory the same so that we can
    // potentially restart ourself later
    $pid = pcntl_fork();
    if ($pid == -1)
        fatal_error("pcntl_fork() failed");
    if ($pid == 0) { // child process that will become the daemon
        // become the leader of a new session
        if (posix_setsid() == -1)
            fatal_error("posix_setsid() failed");

        // redirect STDOUT, STDIN and STDERR; we'll use PHP functionality
        // for this; handle each one separately so the system assigns the
        // correct descriptor; this will not work on Windows or with mod_php; on
        // Linux the next available f descriptor number is used so we open
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
                if (strlen(file_get_contents($PIDFILE)) > 0) {
                    fatal_error("the PID file already exists and is non-empty; "
                        . "this means another instance may be running; if not, "
                        . "then delete the file '$PIDFILE'");
                }
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

// check_include_files() - make sure our dependencies haven't been updated; if
// so, then terminate (or restart) the daemon (i.e. ourself)
function check_include_files() {
    global $RESTART;
    global $INCLUDE_MTIMES;

    foreach ($INCLUDE_MTIMES as $qfile => $t) {
        clearstatcache(true,$qfile); // this is annoying to have to call
        $u = filemtime($qfile);
        if ($t != $u) {
            log_message("library file '$qfile' changed on disk; the daemon "
                . "will restart");
            $RESTART = true;
            terminate();
        }
    }
}

// check_pidfile() - make sure pid file hasn't changed; if it has, then we
// either terminate or restart ourself
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
    global $argv;
    global $PIDFILE;
    global $RESTART;

    if ($RESTART) {
        // keep the pid file as it is; execve() will not change the process id

        log_message("restarting this process");

        // spawn new daemon instance that overrides this instance
        if (!pcntl_exec(PHP_BINARY,$argv))
            fatal_error("fail pcntl_exec() on process restart");

        // control no longer in this process
    }

    if (!is_null($PIDFILE)) {
        // delete pidfile
        unlink($PIDFILE);
    }

    $signaldesc = "";
    if ($signo != -1)
        $signaldesc = "; received signal $signo";

    log_message("this process will exit" . $signaldesc);
    log_message("process terminated");
    exit(0);
}

////////////////////////////////////////////////////////////////////////////////
// main program operation //////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$termfd = @fopen(posix_ctermid(),'r');
if (is_resource($termfd)) {
    // this process is hooked up to a terminal; become a daemon to detach
    fclose($termfd);
    daemonize(); // I normally don't approve of demonizing anyone
}
else {
    // see if a pid file exists and contains our pid; if not, then become a
    // daemon
    if (!file_exists($PIDFILE) ||
            intval(file_get_contents($PIDFILE)) != posix_getpid())
        daemonize();
    else // otherwise reuse pid file as an overwrite process
        log_message("overwrite process started");
}
unset($termfd);

// setup signal handlers
declare(ticks = 1);
pcntl_signal(SIGTERM,'terminate');
pcntl_signal(SIGINT,'terminate');
pcntl_signal(SIGQUIT,'terminate');
pcntl_signal(SIGPIPE,SIG_IGN);

// enter into wait loop
while (true) {
    check_include_files();
    check_pidfile();

    // timeout for a minute
    sleep(60);
}

terminate();
