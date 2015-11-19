<?php

// end the session, then redirect to login page
session_start();
session_destroy();
header('Location: /login.php'); // this returns 302 to client
