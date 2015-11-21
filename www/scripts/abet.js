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
			else
				html += gen(obj.children[child]);
		}
	}
	return ret + ">" + html + "</" + obj.tag + ">";
}
//current global user object
var obj;
//handle internal "navigation"
function navigateInternal(href) {
	switch (href) {
		case "profile":
			obj = getProfile();
			break;
	}
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
//set handlers on inputs after we load content
$(document).ajaxComplete(function() {
	$("#content input").on("change", function() {
		if (typeof(obj[this.id]) !== "undefined") {
			obj[this.id] = $(this).val();
			saveState();
		}
	});
	hijackAnchors();
});

