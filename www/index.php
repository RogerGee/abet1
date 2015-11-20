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
		<script src="scripts/tree.js" type="text/javascript"></script>
		<link rel="stylesheet" href="stylesheets/abet1.css" />
		<link rel="stylesheet" href="stylesheets/tree.css" />
		<script type="text/javascript">
			//current global user object
			var obj;
			//handle internal "navigation"
			function navigateInternal(href) {
				switch (href) {
					case "profile":
						obj = getProfile();
						break;
				}
				//set up input handling
				initInput();
			}
			function initInput() {
				$("#content input").on("change", function() {
					if (typeof(obj[this.id]) !== "undefined") {
						obj[this.id] = this.val();
						saveState();
					}
				});
			}
			//functions for cache maintenance
			function saveState() {
				localStorage.abet1CacheData = JSON.stringify(obj);
			}
			function loadState() {
				return JSON.parse(localStorage.abet1CacheData);
			}
			function clearState() {
				delete localStorage.abet1CacheData;
			}
			function reloadPage() {
				obj = loadState();
				switch (obj.object_name) {
					case "profile":
						loadProfile(obj);
						break;
				}
			}
			//check on document ready for any previous unsaved work
			$(document).ready(function() {
				if (localStorage.abet1CacheData) {
					if (confirm("It seems you left before submitting data.\n" +
						"Would you like to reload your progress?"))
						reloadPage();
					else
						clearState();
				}
				//popup box code
				$("#notif").click(function(event) {
					$("#notifications").fadeToggle(300);
				});
				$("#sett").click(function(event) {
					$("#settings").fadeToggle(300);
				});
				$(".popup").click(function(event) {
					event.stopPropagation();
				});
				$(document).click(function(event) {
					if (event.target.id != "notif" &&
						$("#notifications").css("display") != "none")
						$("#notifications").fadeToggle(300);
					if (event.target.id != "sett" &&
						$("#settings").css("display") != "none")
						$("#settings").fadeToggle(300);
				});
				//hijack internal hrefs
				$(".internal").click(function(event) {
					event.preventDefault();
					navigateInternal($(this).attr("href"));
				});
			});
		</script>
	</head>
	<body>
		<div class="top_bar">
			<a href="/" class="nav_button"><h1>ABET</h1></a>
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
					<li><a href="profile" class="internal">Edit Profile</a></li>
					<li><a href="/logout.php">Logout</a></li>
				</ul>
			</div>
		</div>
		<div class="left_bar">
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
