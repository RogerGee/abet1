<?php

// end the session, then redirect to login page
session_start();
session_destroy();
header('Location: /login.php?logout=1'); // this returns 302 to client
