<?php
	session_start();
	if (array_key_exists('uname'), $_SESSION)
	{
		header('Location: /');
		exit;
	}
	if (array_key_exists('uname', $_POST))
	{
		//we'll need to validate at some point, but for now,
		//just pretend to log someone in
		
		// VALIDATE FROM SQL //
		
		$_SESSION['uname'] = $_POST['uname'];
		$_SESSION['pass'] = $_POST['pass'];
		header('Location: /');
		exit;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>login</title>
	</head>
	<body>
		<center>
			<div>
				Login:<br/>
				<form method="POST" action="login.php">
					<table>
						<tr>
							<td>Username</td>
							<td><input id="uname" type="text"></input></td>
						</tr>
						<tr>
							<td>Password</td>
							<td><input id="pass" type="password"></input></td>
						</tr>
					</table>
					<input type="submit" value="login"></input>
				</form>
			</div>
		</center>
	</body>
</html>