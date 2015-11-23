/* here goes all general use javascript */
//function which can generate html from a nested javascript object
function gen(obj) {
	var ret = "<" + obj.tag;
	for (var prop in obj)
		if (prop != "children" && prop != "tag")
			ret += " " + prop + "='" + obj[prop] + "'";
	var html = "";
	if (obj.children) {
		for (var child in obj.children) {
			if (typeof(obj.children[child]) === 'string')
				html += obj.children[child];
			else if (typeof(obj.chilren[child]) === 'object')
				html += gen(obj.children[child]);
		}
	}
	return ret + ">" + html + "</" + obj.tag + ">";
}
//current global user object
var obj;
//handle internal "navigation"
function navigateInternal(href) {
	obj = window[href]();
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
	$(".internal").click(function(event) {
		event.preventDefault();
		var href = $(this).attr("href");
		if (!localStorage.abet1CacheData) {
			navigateInternal(href);
		} else {
			$.confirm("Unsubmited Work", "You have unsubmited data on this page.\n" +
				"Are you sure you wish to leave? All changes will be lost",
				"Leave", "Stay").accept(function() {navigateInternal(href);});
		}
	});
}
//check on document ready for any previous unsaved work
$(document).ready(function() {
	if (localStorage.abet1CacheData) {
		$.confirm("Unsaved Data", "It seems you left before submitting data.\n" +
			"Would you like to reload your progress?", "Yes", "No")
			.accept(reloadPage).decline(clearState);
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
	hijackAnchors();
});
//set handlers on inputs and textareas
function initInputs() {
	$("#content input, #content textarea").on("change", function() {
		if (typeof(obj[this.id]) !== "undefined") {
			obj[this.id] = $(this).val();
			saveState();
		}
	});
	hijackAnchors();
}
$(document).ajaxError(function(event, jqxhr) {
	if (jqxhr.status == 401)
		window.location.href = "/login.php";
});

