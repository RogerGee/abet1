/* set up tree */
function initTree() {
	$.when($(".tree ul").each(function() {
		var h = parseInt($(this).css("height"));
		//set a dummy attribute h to the height of folded content
		$(this).attr("h", h);
		//subtract this height from all parent uls
		$(this).parents(".tree ul").each(function() {
			$(this).attr("h", $(this).attr("h") - h);
		});
	})).done(function() {
		//init all to closed
		$(".tree ul").css("height", 0);
	});
	$(".tree div").click(function() {
		var ul = $($(this).parent()).children("ul");
		if ($(ul).css("height") == "0px") {
			var h = parseInt($(ul).attr("h"));
			//open this ul
			$(ul).css("height", h);
			//and expand all parents by appropriate amount
			$(ul).parents(".tree ul").each(function() {
				$(this).css("height", parseInt($(this).css("height")) + h);
			});
		} else {
			var h = parseInt($(ul).css("height"));
			//cache the current height for reopening
			$(ul).attr("h", h);
			//close this ul
			$(ul).css("height", 0);
			//and shrink all parents by appropriate amount
			$(ul).parents(".tree ul").each(function() {
				$(this).css("height", parseInt($(this).css("height")) - h);
			});
		}
	});
}

