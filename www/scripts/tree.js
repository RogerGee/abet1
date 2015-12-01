(function($) {
	$.fn.extend({
		/* set up tree */
		tree: function() {
			if ($(this).prop("tagName") != "UL")
				return;
			var root = this;
			$(root).addClass("tree");
			//mark any already open uls, in case this is a re-init
			$(root).find("ul").each(function() {
				if ($(this).is("[h]") && parseInt($(this).css("height")))
					$(this).attr("open", true);
			});
			//make sure tree is fully expanded
			$(root).find("ul").css("height", "auto");
			//process heights
			$(root).find("ul").each(function() {					
				var h = parseInt($(this).css("height"));
				//set a dummy attribute h to the height of folded content
				$(this).attr("h", h);
				//subtract this height from the parent ul
				$(this).parent().parent().attr("h",$(this).parent().parent().attr("h")-h);
			});
			//close those that weren't already open
			$(root).find("ul").each(function() {
				if ($(this).is("[open]")) {
					$(this).css("height", $(this).attr("h"));
					$(this).attr("open", false);
				} else {
					$(this).css("height", 0);
				}
			});
			//remove any previously set click listeners
			$(root).find("div").off("click");
			//add the click listener
			$(root).find("div").on("click", function() {
				var ul = $($(this).parent()).children("ul");
				if ($(ul).css("height") == "0px") {
					var h = parseInt($(ul).attr("h"));
					//open this ul
					$(ul).css("height", h);
					$(ul).css("transition-duration", (h/200)+"s");
					//and expand all parents by appropriate amount
					$(ul).parents(".tree ul").each(function() {
						$(this).attr("h", parseInt($(this).attr("h")) + h);
						$(this).css("height", $(this).attr("h"));
						$(this).css("transition-duration", (h/200)+"s");
					});
				} else if (parseInt($(ul).css("height")) == $(ul).attr("h")) {
					var h = $(ul).attr("h");
					//close this ul
					$(ul).css("height", 0);
					$(ul).css("transition-duration", (h/200)+"s");
					//and shrink all parents by appropriate amount
					$(ul).parents(".tree ul").each(function() {
						$(this).attr("h", parseInt($(this).attr("h")) - h);
						$(this).css("height", $(this).attr("h"));
						$(this).css("transition-duration", (h/200)+"s");
					});
				}
			});
		}
	});
})(jQuery);

