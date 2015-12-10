<?php

// include needed files; update the include path to find the libraries
$paths = array(
	get_include_path(),
	'/usr/lib/abet1',
	'/usr/local/lib/abet1'
);
set_include_path(implode(PATH_SEPARATOR,$paths));
require_once 'abet1-login.php';

// check authentication; if none found then redirect to the login page
if (!abet_is_authenticated()) {
	header('Location: /login.php'); // returns 302 to client
	exit;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>ABET</title>
		<script src="scripts/jquery.min.js" type="text/javascript"></script>
		<script src="scripts/jquery-ui.min.js" type="text/javascript"></script>
		<script src="scripts/abet.js" type="text/javascript"></script>
		<script src="scripts/home.js" type="text/javascript"></script>
		<script src="scripts/profile.js" type="text/javascript"></script>
		<script src="scripts/navigation.js" type="text/javascript"></script>
		<script src="scripts/content.js" type="text/javascript"></script>
		<script src="scripts/rubric.js" type="text/javascript"></script>
		<script src="scripts/worksheet.js" type="text/javascript"></script>
		<script src="scripts/tree.js" type="text/javascript"></script>
		<script src="scripts/confirm.js" type="text/javascript"></script>
		<?php if(abet_is_admin_authenticated()) { ?>
		<!-- Admin only scripts go here -->
		<script src="scripts/usercreate.js" type="text/javascript"></script>
		<script src="scripts/program.js" type="text/javascript"></script>
		<script src="scripts/assessment.js" type="text/javascript"></script>
		<script src="scripts/characteristics.js" type="text/javascript"></script>
		<script src="scripts/course.js" type="text/javascript"></script>
		<?php } ?>
		<link rel="stylesheet" href="stylesheets/abet.css" />
		<link rel="stylesheet" href="stylesheets/tree.css" />
		<link rel="stylesheet" href="stylesheets/confirm.css" />
		<script type="text/javascript">
			user = "<?php echo $_SESSION['user']; ?>";
			read_only = <?php echo abet_is_observer() ? 'true' : 'false'; ?>;
		</script>
	</head>
	<body>
		<div class="top_bar">
			<a href="loadHome" class="nav_button internal"><h1>ABET</h1></a>
			<input type="text" placeholder="search" class="search"></input>
			<div class="top_icons">
				<img id="notif" src="resources/notif.png" class="icon"></img>
				<img id="sett" src="resources/settings.png" class="icon"></img>
			</div>
			<div id="notifications" class="popup">
				This is where I would put my notifications
				<br /><br /><br /><br /><br />
				If I had any!<br />
			</div>
			<div id="settings" class="popup">
				<ul>
					<li><a href="getProfile" class="internal">Edit Profile</a></li>
					<li><a href="/logout.php">Logout</a></li>
				</ul>
			</div>
		</div>
		<div id="left_bar" class="left_bar"></div>
		<div id="content" class="content"></div>
	</body>
</html>
