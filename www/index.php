<?php
	session_start();
	//we might also want to validate that the uname and
	//password match the database entries, not sure if that's necessary
	if (!array_key_exists('uname', $_SESSION))
	{
		header('Location: /login.php');
		exit;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>ABET</title>
		<script src="scripts/jquery.min.js" type="text/javascript"></script>
		<script src="scripts/jquery-ui.min.js" type="text/javascript"></script>
		<script src="scripts/tree.js" type="text/javascript"></script>
		<link rel="stylesheet" href="stylesheets/abet1.css" />
		<link rel="stylesheet" href="stylesheets/tree.css" />
		<script type="text/javascript">
			//current global user object
			var obj;
			//function to build a page based on user object
			function loadPage(data) {
				obj = data;
				//switch for view, then build page
			}
			//handle internal "navigation"
			function navigateInternal(href) {
				$.ajax({url:href,dataType:"json"}).done(function(data) {
					loadPage(JSON.parse(data));
				});
			}
			function submit() {
				$.ajax({type:"POST",url:"",data:JSON.stringify(obj)});
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
			//check on document ready for any previous unsaved work
			$(document).ready(function() {
				if (localStorage.abet1CacheData) {
					if (confirm("It seems you left before submitting data.\n" +
						"Would you like to reload your progress?"))
						loadPage(loadState());
					else
						clearState();
				}
				//notification box code
				$("#notif").click(function(event) {
					$("#notifications").fadeToggle(300);
					event.stopPropagation();
				});
				$("#notifications").click(function(event) {
					event.stopPropagation();
				});
				$(document).click(function() {
					if ($("#notifications").css("display") != "none")
						$("#notifications").fadeToggle(300);
				});
			});
		</script>
	</head>
	<body>
		<div class="top_bar">
			<a href="/" class="nav_button"><h1>ABET</h1></a>
			<div class="top_icons">
				<img id="notif" src="resources/notif.png" class="notif"></img>
			</div>
			<div id="notifications" class="notifpopup">
				This is where I would put my notifications
				<br /><br /><br /><br /><br />
				If I had any!<br />
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