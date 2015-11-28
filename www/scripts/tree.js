(function($) {
	/* set up tree */
	$.fn.extend({
		tree: function() {
			if ($(this).prop("tagName") != "UL")
				return;
			var root = this;
			$(root).addClass("tree");
			$.when($(root).find("ul").each(function() {
				var h = parseInt($(this).css("height"));
				//set a dummy attribute h to the height of folded content
				$(this).attr("h", h);
				//subtract this height from the parent ul
				$(this).parent().parent().attr("h",$(this).parent().parent().attr("h")-h);
			})).done(function() {
				//init all to closed
				$(root).find("ul").css("height", 0);
			});
			$(root).find("div").click(function() {
				var ul = $($(this).parent()).children("ul");
				if ($(ul).css("height") == "0px") {
					var h = parseInt($(ul).attr("h"));
					//open this ul
					$(ul).css("height", h);
					//and expand all parents by appropriate amount
					$(ul).parents(".tree ul").each(function() {
						$(this).attr("h", parseInt($(this).attr("h")) + h);
						$(this).css("height", $(this).attr("h"));
					});
				} else {
					var h = parseInt($(ul).css("height"));
					//close this ul
					$(ul).css("height", 0);
					//and shrink all parents by appropriate amount
					$(ul).parents(".tree ul").each(function() {
						$(this).attr("h", parseInt($(this).attr("h")) - h);
						$(this).css("height", $(this).attr("h"));
					});
				}
			});
		}
	});
})(jQuery);

