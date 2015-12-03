(function($) {
	$.fn.extend({
		/* set up tree */
		tree_old: function() {
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
			//close those that weren't already open, and open those that were
			$(root).find("ul").each(function() {
				if ($(this).is("[open]")) {
					$(this).attr("open", false);
					var h = parseInt($(this).attr("h"));
					$(this).css("height", h);
					$(this).parents(".tree ul").each(function() {
						$(this).attr("h", parseInt($(this).attr("h")) + h);
						$(this).css("height", $(this).attr("h"));
					});
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
		},
		tree: function() {
			//make sure this is a ul, nothing else can be a tree
			if (!$(this).is("ul"))
				return;
			var root = this;
			$(root).addClass("tree");
			//make sure tree is fully expanded
			$(root).find("ul").css("height", "auto");
			//close those that weren't already open
			$(root).find("ul").each(function() {
				if (!$(this).is("[open]")) {
					$(this).css("height", 0);
				}
			});
			//remove any previously set click listeners
			$(root).find("div").off("click");
			//add the click listener
			$(root).find("div").on("click", function() {
				var ul = $($(this).parent()).children("ul");
				//make sure all parents are open
				var open = true;
				$(ul).parents(".tree ul").each(function() {
					open &= $(this).is("[open]");
				}); if (!open) return;
				//set all parent uls to auto
				$(ul).parents(".tree ul").css("height", "auto");
				if (!$(ul).is("[open]")) {
					//set open attribute
					$(ul).attr("open", true);
					//hack to silently expand, grab height, and return to previous size
					var h1 = parseInt($(ul).css("height"));
					$(ul).css("height", "auto");
					var h2 = parseInt($(ul).css("height"));
					$(ul).css("height", h1);
					//set duration based on the change in height
					$(ul).css("transition-duration", ((h2-h1)/200)+"s");
					//set the new height after a millisecond
					setTimeout(function() {$(ul).css("height", h2);}, 1);
				} else {
					//set open attribute
					$(ul).attr("open", false);
					//change the height from auto to specific height
					var h = parseInt($(ul).css("height"));
					$(ul).css("height", h);
					//set duration based on the change in height
					$(ul).css("transition-duration", (h/200)+"s");
					//set the new height after a millisecond
					setTimeout(function() {$(ul).css("height", 0);}, 1);
				}
			});
		}
	});
})(jQuery);

