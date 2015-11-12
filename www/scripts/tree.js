$(document).ready(function() {
	$.when($(".tree ul").each(function() {
		$(this).attr("h", $(this).css("height"));
	})).done(function() {
		$(".tree ul").css("height", 0);
	});
	$(".tree div").click(function() {
		var ul = $($(this).parent()).children("ul");
		if ($(ul).css("height") == "0px")
			$(ul).css("height", $(ul).attr("h"));
		else
			$(ul).css("height", 0);
	});
});