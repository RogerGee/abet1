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
		<script src="scripts/profile.js" type="text/javascript"></script>
		<script src="scripts/navigation.js" type="text/javascript"></script>
		<script src="scripts/tree.js" type="text/javascript"></script>
		<script src="scripts/confirm.js" type="text/javascript"></script>
		<?php if(abet_is_admin_authenticated()) { ?>
		<!-- Admin only scripts go here -->
		<script src="scripts/usercreate.js" type="text/javascript"></script>
		<?php } ?>
		<link rel="stylesheet" href="stylesheets/abet.css" />
		<link rel="stylesheet" href="stylesheets/tree.css" />
		<link rel="stylesheet" href="stylesheets/confirm.css" />
		<script type="text/javascript">
			user = "<?php echo $_SESSION['user']; ?>";
		</script>
	</head>
	<body>
		<div class="top_bar">
			<a href="/" class="nav_button"><h1>ABET</h1></a>
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
		<div id="left_bar" class="left_bar">
			Navigation
			<ul class="tree">
				<li>
					<div>content</div>
					<ul>
						<li>
							<div><a href="#">inner content</a></div>
						</li>
						<li>
							<div><a href="#">inner content</a></div>
						</li>
						<li>
							<div>inner folder</div>
							<ul>
								<li><div><a href="#">inner content again</a></div></li>
							</ul>
						</li>
					</ul>
				</li>
				<li>
					<div>hi</div>
					<ul>
						<li>
							<div><a href="#">hi inner content</a></div>
						</li>
						<li>
							<div><a href="#">whooo inner content</a></div>
						</li>
					</ul>
				</li>
				<li>
					<div><a href="#">hello</a></div>
				</li>
			</ul>
		</div>
		<div id="content" class="content">
			this is content
			<br/><br/><br/><br/><br/><br/><br/><br/><br/>
			<br/><br/><br/><br/><br/><br/><br/><br/><br/>
			<br/><br/><br/><br/><br/><br/><br/><br/><br/>
			<br/><br/><br/><br/><br/><br/><br/><br/><br/>
			<br/><br/><br/><br/><br/><br/><br/><br/><br/>
			<br/><br/><br/><br/><br/><br/><br/><br/><br/>
			look scrollbars
		</div>
	</body>
</html>
