/* here goes all general use javascript */
//function which can generate html from a nested javascript object
function gen(obj) {
	var ret = "<" + obj.tag;
	for (var prop in obj)
		if (prop != "children" && prop != "tag" && obj[prop] !== null)
			ret += " " + prop + "='" + obj[prop] + "'";
	var html = "";
	if (obj.children !== null && obj.children !== undefined) {
		if (obj.children instanceof Array) {
			for (var child in obj.children) {
				if (typeof(obj.children[child]) !== 'object')
					html += obj.children[child];
				else if (obj.children[child] !== null)
					html += gen(obj.children[child]);
			}
		} else if (typeof(obj.children) !== 'object') { 
			html += obj.children;
		} else {
			html += gen(obj.children)
		}
	}
	return ret + ">" + html + "</" + obj.tag + ">";
}
//current global user object
var obj;
//current user's username
var user;
//handle internal "navigation"
function navigateInternal(href, id) {
	window[href](id);
}
//functions for cache maintenance
function hasState() {
	return localStorage[user] !== undefined;
}
function saveState() {
	localStorage[user] = JSON.stringify(obj);
}
function loadState() {
	return JSON.parse(localStorage[user]);
}
function cutModif(id) {
	if (obj._modifs && obj._modifs[id])
		delete obj._modifs[id];
	if (Object.keys(obj._modifs).length == 0) {
		delete obj._modifs;
		clearState();
	}
}
function clearState() {
	delete localStorage[user];
}
function reloadPage() {
	obj = loadState();
	window[obj.load_func](obj);
}
//bit of js to make inputs for telephone numbers
function initPhone() {
	$("input[type=phone]").on("input", function(event) {
		var start = this.selectionStart;
		var string = $(this).val();
		string = string.replace(/[^0-9\-]/g, '');
		if (string.indexOf('-') !== -1) {
			start -= (start > 4) ? (start > 8 && string.length > 9) ? 2 : 1 : 0;
			string = string.replace(/\D/g,'');
		}
		if (string.length > 3)
			string = string.slice(0,3) + "-" + string.slice(3);
		if (string.length > 8)
			string = string.slice(0,7) + "-" + string.slice(7);
		if (string.length > 12)
			string = string.slice(0, 12)
		start += (start > 3) ? (start > 6) ? 2 : 1 : 0;
		$(this).val(string);
		this.setSelectionRange(start, start);
	});
	$("input[type=phone]").on("keydown", function(event) {
		var key = event.keyCode || event.charCode;
		var length = $(this).val().length;
		var start = this.selectionStart, end = this.selectionEnd;
		start -= (start > 3) ? (start > 7 && length > 8) ? 2 : 1 : 0;
		end -= (end > 3) ? (end > 7 && length > 8) ? 2 : 1 : 0;
		if (key == 8 || key == 46) {
			$(this).val($(this).val().replace(/\D/g,''));
			this.setSelectionRange(start, end);
		}
	});
}
//hijack internal hrefs
function hijackAnchors() {
	$(".internal").each(function() {
		if ($._data(this, "events") && $._data(this, "events").click)
			return;
		$(this).click(function(event) {
			event.preventDefault();
			var href = $(this).attr("href");
			var id = this.id;
			if (!hasState()) {
				navigateInternal(href, id);
			} else {
				$.confirm("Unsubmited Work", "You have unsubmited data on this page.\n"+
					"Are you sure you wish to leave? All changes will be lost.",
					"Leave", "Stay").accept(function() {
					//scrub cache
					clearState();
					//"navigate"
					navigateInternal(href, id);
				});
			}
		});
	});
}
$(document).ready(function() {
	//load the home page
	loadHome();
	//check on document ready for any previous unsaved work, or a refresh
	if (sessionStorage[user]) {
		if (hasState()) {
			reloadPage();
			if (!obj._modifs)
				clearState();
		}
		$("#left_bar").html(sessionStorage[user]);
	} else if (hasState()) {
		$.confirm("Unsaved Data", "It seems you left before submitting data.\n" +
			"Would you like to reload your progress?", "Yes", "No")
			.accept(reloadPage).decline(clearState);
	}
	//popup box code
	initSearch();
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
		if (event.target.id != "search" &&
			$("#search_results").css("display") != "none")
			$("#search_results").fadeToggle(300);
	});
	$(window).resize(function() {
		$(".tree").tree();
	});
	loadNavigation();
	setInterval(loadNavigation, 60000);
});
//assign to nested obj
function assign(o, s, v) {
	var c = s.split(':');
	if (c.length == 1) {
		o[s] = v;
		return;
	}
	var f = c.shift();
	if (typeof(o[f]) === 'undefined')
		o[f] = {};
	assign(o[f], c.join(':'), v);
}
//set handlers on properties of the user object
function initInputs(skip) {
	if (!skip && read_only) {
		$("#content .property").attr("disabled", true);
		$("#content input[type=button]").remove();
		$("#content select:not(.property)").remove();
	} else {
		$("#content .property").on("change", function() {
			assign(obj, $(this).attr("id"), $(this).val());
			if (!obj._modifs) obj._modifs = {};
			obj._modifs[$(this).attr("id")] = '';
			saveState();
		});
	}
}
//handle 401 (user not authenticated/logged in) globally, redirect to login
$(document).ajaxError(function(event, jqxhr) {
	if (jqxhr.status == 401)
		window.location.href = "/login.php";
});
$(window).bind("beforeunload", function(event) {
	sessionStorage[user] = $("#left_bar").html();
	saveState();
});

