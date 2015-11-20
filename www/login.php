<?php

// include needed files; update the include path to find the libraries
$paths = array(
	'/usr/lib/abet1',
	'/usr/local/lib/abet1',
	'../lib',
	get_include_path()
);
set_include_path(implode(PATH_SEPARATOR,$paths));
require_once 'abet1-login.php';

// if the user is already authenticated, then redirect to the main page
if (abet_is_authenticated()) {
	header('Location: /'); // returns 302 to client
	exit;
}

// if the page is responding to a POST request, then
if ($_SERVER['REQUEST_METHOD'] == 'POST' && array_key_exists('user', $_POST)
	&& array_key_exists('passwd',$_POST))
{
	if (!abet_login($_POST['user'],$_POST['passwd'])) {
		header('Location: /login.php?login=1'); // returns 302 to client
		exit;
	}

	header('Location: /'); // returns 302 to client
	exit;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>ABET - login to site</title>
		<link rel="stylesheet" href="stylesheets/login.css" />
	</head>
	<body>
		<center>
			<div class="abet1-login">
				<h1>Login to ABET</h1>
				<form method="POST" action="login.php">
					<div class="abet1-login-content">
						<table class="abet1-login-table">
							<tr>
								<td>Username</td>
								<td>
									<input name="user" type="text"></input>
								</td>
							</tr>
							<tr>
								<td>Password</td>
								<td>
									<input name="passwd" type="password"></input>
								</td>
							</tr>
						</table>
						<input class="abet1-login-button" type="submit" value="LOGIN"></input>
					</div>
				</form>
				<p>
					For security reasons, make sure to log out once you have
					finished using the system.
				</p>
<?php
// if 'login' was set to 1 then the user failed a login attempt; we produce a
// failed login message only if the session had a record of the user login attempt
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	if (array_key_exists('login',$_GET)
		&& intval($_GET['login']) == 1 && isset($_SESSION)
		&& array_key_exists('user',$_SESSION))
	{
		echo '<p class="abet-failed-login-message">Login unsuccessful. Please try again.</p>';

		// clear the session so the URL /login.php?login=1 doesn't generate future login failure
		// messages
		session_unset();
		session_destroy();
	}
	else if (array_key_exists('logout',$_GET) && intval($_GET['logout']) == 1) {
		echo '<p>You have been logged out. We\'ll leave the light on for you!</p>';
	}
}
?>
			</div>
		</center>
	</body>
</html>
